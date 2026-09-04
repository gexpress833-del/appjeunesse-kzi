<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Choix de l'événement pour la prise de présence.
     * Un responsable est automatiquement cantonné à son département.
     */
    public function pick()
    {
        $user = auth()->user();

        return view('attendances.pick', [
            'upcoming' => Event::upcoming()->take(5)->get(),
            'past' => Event::past()->take(10)->get(),
            'dept' => $user->isResponsable() ? $user->dept : null,
            'departments' => $user->isResponsable() ? collect() : Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Feuille de présence d'un événement pour un département.
     */
    public function sheet(Request $request, Event $event)
    {
        $user = auth()->user();
        $dept = $user->isResponsable() ? $user->dept : $request->query('dept', Department::orderBy('name')->value('name'));

        abort_if(blank($dept), 404, 'Aucun département sélectionné.');

        if (! $user->isResponsable()) {
            abort_unless(Department::where('name', $dept)->exists(), 404, 'Département inconnu.');
        }

        $members = Member::where('dept', $dept)->orderBy('name')->get();

        $existing = Attendance::where('event_id', $event->id)
            ->whereIn('member_id', $members->pluck('id'))
            ->get()
            ->keyBy('member_id');

        return view('attendances.sheet', compact('event', 'dept', 'members', 'existing'));
    }

    /**
     * Enregistrement (upsert) des statuts de présence.
     */
    public function store(Request $request, Event $event)
    {
        $user = auth()->user();

        $data = $request->validate([
            'dept' => ['required', 'string', 'exists:departments,name'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['in:present,absent,late,excused'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        // Un responsable ne peut faire l'appel que de son département
        if ($user->isResponsable() && $data['dept'] !== $user->dept) {
            abort(403, 'Vous ne pouvez prendre les présences que pour votre département.');
        }

        $memberIds = Member::where('dept', $data['dept'])->pluck('id');

        foreach ($data['statuses'] as $memberId => $status) {
            $memberId = (int) $memberId;

            if (! $memberIds->contains($memberId)) {
                continue; // pas un membre du département concerné
            }

            Attendance::updateOrCreate(
                ['member_id' => $memberId, 'event_id' => $event->id],
                ['status' => $status, 'notes' => $data['notes'][$memberId] ?? null]
            );
        }

        return redirect()->route('attendances.sheet', ['event' => $event, 'dept' => $data['dept']])
            ->with('success', 'Présences enregistrées pour le département '.$data['dept'].'.');
    }

    /**
     * Rapport global des présences (secrétariat / admin).
     */
    public function report(Request $request)
    {
        $query = Attendance::query()
            ->join('members', 'members.id', '=', 'attendances.member_id')
            ->join('events', 'events.id', '=', 'attendances.event_id')
            ->select('attendances.*')
            ->with(['member', 'event']);

        if ($request->filled('event_id')) {
            $query->where('attendances.event_id', $request->event_id);
        }

        if ($request->filled('dept')) {
            $query->where('members.dept', $request->dept);
        }

        if ($request->filled('status')) {
            $query->where('attendances.status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('events.date', '>=', $request->from.' 00:00:00');
        }

        if ($request->filled('to')) {
            $query->where('events.date', '<=', $request->to.' 23:59:59');
        }

        $rows = $query->orderByDesc('events.date')->orderBy('members.name')->paginate(50)->withQueryString();

        // Résumé par département sur la sélection courante
        $summaryQuery = clone $query;
        $summary = (clone $summaryQuery)
            ->selectRaw('members.dept, COUNT(*) as total,
                SUM(CASE WHEN attendances.status = \'present\' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = \'late\' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN attendances.status = \'excused\' THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN attendances.status = \'absent\' THEN 1 ELSE 0 END) as absent')
            ->groupBy('members.dept')
            ->orderBy('members.dept')
            ->get()
            ->each(fn ($r) => $r->rate = $r->total > 0 ? (int) round((($r->present + $r->late) / $r->total) * 100) : 0);

        return view('attendances.report', [
            'rows' => $rows,
            'summary' => $summary,
            'events' => Event::orderByDesc('date')->take(30)->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['event_id', 'dept', 'status', 'from', 'to']),
        ]);
    }

    /**
     * Génère le rapport PDF avec les mêmes filtres que l'écran de rapport.
     */
    public function exportPdf(Request $request)
    {
        $query = $this->filteredReportQuery($request);
        $rows = $query->orderByDesc('events.date')->orderBy('members.name')->get();
        $summary = $this->reportSummary(clone $query);

        return Pdf::loadView('attendances.pdf', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $request->only(['event_id', 'dept', 'status', 'from', 'to']),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('rapport-presences-'.now()->format('Y-m-d').'.pdf');
    }

    protected function filteredReportQuery(Request $request)
    {
        $query = Attendance::query()
            ->join('members', 'members.id', '=', 'attendances.member_id')
            ->join('events', 'events.id', '=', 'attendances.event_id')
            ->select('attendances.*')
            ->with(['member', 'event']);

        return $query
            ->when($request->filled('event_id'), fn ($query) => $query->where('attendances.event_id', $request->event_id))
            ->when($request->filled('dept'), fn ($query) => $query->where('members.dept', $request->dept))
            ->when($request->filled('status'), fn ($query) => $query->where('attendances.status', $request->status))
            ->when($request->filled('from'), fn ($query) => $query->where('events.date', '>=', $request->from.' 00:00:00'))
            ->when($request->filled('to'), fn ($query) => $query->where('events.date', '<=', $request->to.' 23:59:59'));
    }

    protected function reportSummary($query)
    {
        return $query
            ->selectRaw('members.dept, COUNT(*) as total,
                SUM(CASE WHEN attendances.status = \'present\' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = \'late\' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN attendances.status = \'excused\' THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN attendances.status = \'absent\' THEN 1 ELSE 0 END) as absent')
            ->groupBy('members.dept')
            ->orderBy('members.dept')
            ->get()
            ->each(fn ($row) => $row->rate = $row->total > 0 ? (int) round((($row->present + $row->late) / $row->total) * 100) : 0);
    }
}
