<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use App\Notifications\MemberAddedToDepartment;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    private const UNASSIGNED_DEPARTMENT = '__none__';

    /**
     * Annuaire consultable par tous les comptes actifs,
     * avec recherche et filtre par département.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $members = Member::query()
            ->with('department')
            // Un responsable ne voit que son département dans l'annuaire
            ->when($user->isResponsable(), fn ($q) => $q->where('dept', $user->dept))
            ->when($request->filled('dept'), fn ($q) => $q->where('dept', $request->dept))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('members.index', [
            'members' => $members,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function show(Member $member)
    {
        $attendances = Attendance::with('event')
            ->where('member_id', $member->id)
            ->latest('id')->take(10)->get();

        $total = $member->attendances()->count();
        $ok = $member->attendances()->whereIn('status', ['present', 'late'])->count();

        return view('members.show', [
            'member' => $member->load('department'),
            'attendances' => $attendances,
            'rate' => $total > 0 ? (int) round(($ok / $total) * 100) : null,
            'total' => $total,
        ]);
    }

    public function create()
    {
        return view('members.form', [
            'member' => new Member,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $data = $this->validated($request);

        $data = $this->scopeDept($data);
        $data = $this->handlePhoto($request, $cloudinary, $data);

        $member = Member::create($data);

        $this->notifyDepartmentResponsable($member);

        return redirect()->route('members.index')->with('success', 'Membre ajouté au répertoire.');
    }

    public function edit(Member $member)
    {
        $this->authorizeManage($member);

        return view('members.form', [
            'member' => $member,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Member $member, CloudinaryService $cloudinary)
    {
        $this->authorizeManage($member);

        $data = $this->validated($request, $member->id);
        $data = $this->scopeDept($data);
        $data = $this->handlePhoto($request, $cloudinary, $data);

        // Un responsable ne peut pas déplacer un membre hors de son département
        if (auth()->user()->isResponsable()) {
            $data['dept'] = $member->dept;
        }

        $member->update($data);

        return redirect()->route('members.show', $member)->with('success', 'Fiche mise à jour.');
    }

    public function destroy(Member $member)
    {
        $this->authorizeManage($member);
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Membre supprimé du répertoire.');
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $emailRule = $ignoreId
            ? ['nullable', 'email', 'max:150', "unique:members,email,{$ignoreId}"]
            : ['nullable', 'email', 'max:150', 'unique:members,email'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'dept' => ['required', 'string'],
            'role' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => $emailRule,
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['role'] = filled($data['role'] ?? null) ? $data['role'] : 'Fidèle';

        return $data;
    }

    /**
     * Un responsable de département ne gère que les membres de son département.
     */
    protected function scopeDept(array $data): array
    {
        $user = auth()->user();

        if ($user->isResponsable()) {
            $data['dept'] = $user->dept;
        } elseif ($data['dept'] === self::UNASSIGNED_DEPARTMENT) {
            $data['dept'] = null;
        } elseif (! Department::where('name', $data['dept'])->exists()) {
            abort(422, 'Département inconnu.');
        }

        return $data;
    }

    protected function handlePhoto(Request $request, CloudinaryService $cloudinary, array $data): array
    {
        if (! $request->hasFile('profile_photo')) {
            return $data;
        }

        $uploaded = $cloudinary->upload($request->file('profile_photo'), 'appjeune-kzi/members');
        $data['profile_photo_url'] = $uploaded['url'];

        return $data;
    }

    protected function authorizeManage(Member $member): void
    {
        $user = auth()->user();

        $allowed = $user->isAdmin() || $user->isSecretariat()
            || ($user->isResponsable() && $user->dept === $member->dept);

        abort_unless($allowed, 403, 'Vous ne pouvez gérer que les membres de votre département.');
    }

    protected function notifyDepartmentResponsable(Member $member): void
    {
        $responsibles = User::query()
            ->where('role', 'responsable')
            ->where('dept', $member->dept)
            ->get();

        foreach ($responsibles as $responsable) {
            $responsable->notify(new MemberAddedToDepartment($member, $member->dept));
        }
    }
}
