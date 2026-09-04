<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SocialVisit;
use App\Models\User;
use Illuminate\Http\Request;

class SocialVisitController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = SocialVisit::query()->with(['member', 'assignee'])->orderBy('visit_date');

        if ($user->isSocialResponsable()) {
            $query->whereHas('member', fn ($members) => $members->where('dept', 'Social'));
        } elseif (! $user->isAdmin() && ! $user->isSecretariat()) {
            $query->where('member_id', $user->member()?->id ?? 0);
        }

        return view('social-visits.index', ['visits' => $query->paginate(20)]);
    }

    public function create()
    {
        $this->authorizeManage();

        return view('social-visits.form', $this->formData(new SocialVisit([
            'visit_date' => now()->addDay()->setTime(9, 0),
        ])));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();

        SocialVisit::create($data);

        return redirect()->route('social-visits.index')->with('success', 'Visite sociale planifiée.');
    }

    public function edit(SocialVisit $socialVisit)
    {
        $this->authorizeManage($socialVisit);

        return view('social-visits.form', $this->formData($socialVisit));
    }

    public function update(Request $request, SocialVisit $socialVisit)
    {
        $this->authorizeManage($socialVisit);
        $socialVisit->update($this->validated($request));

        return redirect()->route('social-visits.index')->with('success', 'Visite sociale mise à jour.');
    }

    public function destroy(SocialVisit $socialVisit)
    {
        $this->authorizeManage($socialVisit);
        $socialVisit->delete();

        return redirect()->route('social-visits.index')->with('success', 'Visite sociale supprimée.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'visit_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:150'],
            'status' => ['required', 'in:planned,completed,cancelled'],
            'report_notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    protected function formData(SocialVisit $visit): array
    {
        $user = auth()->user();

        return [
            'visit' => $visit,
            'members' => Member::query()->when($user->isSocialResponsable(), fn ($query) => $query->where('dept', 'Social'))->orderBy('name')->get(),
            'assignees' => User::query()->where('status', 'active')->orderBy('full_name')->get(),
        ];
    }

    protected function authorizeManage(?SocialVisit $visit = null): void
    {
        $user = auth()->user();
        $allowed = $user->isAdmin() || $user->isSecretariat() || $user->isSocialResponsable();

        if ($allowed && $visit && $user->isSocialResponsable()) {
            $allowed = $visit->member()->where('dept', 'Social')->exists();
        }

        abort_unless($allowed, 403, 'Cette fonctionnalité est réservée au Département Social.');
    }
}
