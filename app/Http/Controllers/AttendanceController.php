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
    private const UNASSIGNED_DEPARTMENT = '__none__';

    /**
     * Choix de l'événement pour la prise de présence.
     * Un responsable voit les événements globaux et ceux de son département.
     */
    public function pick()
    {
        $user = auth()->user();

        return view('attendances.pick', [
            'upcoming' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->upcoming()->take(5)->get(),
            'past' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->past()->take(10)->get(),
            'dept' => $user->isResponsable() ? $user->dept : request('dept'),
            'departments' => $user->isResponsable() ? collect() : Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Feuille de présence d'un événement pour un département.
     */
    public function sheet(Request $request, Event $event)
    {
        $user = auth()->user();

        if ($user->isResponsable() && filled($event->dept) && $event->dept !== $user->dept) {
            abort(403, 'Vous ne pouvez consulter les présences que de votre département.');
        }

        $dept = $user->isResponsable() ? $user->dept : $this->normalizeDepartment($request->query('dept'));

        abort_if(! $user->isResponsable() && $request->missing('dept'), 422, 'Sélectionnez un groupe de membres.');

        if (! $user->isResponsable() && filled($dept)) {
            abort_unless(Department::where('name', $dept)->exists(), 404, 'Département inconnu.');
        }

        $members = Member::query()
            ->when($dept === null, fn ($query) => $query->whereNull('dept'))
            ->when(filled($dept), fn ($query) => $query->where('dept', $dept))
            ->orderBy('name')
            ->get();

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
            'dept' => ['required', 'string'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['in:present,absent,late,excused'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $data['dept'] = $this->normalizeDepartment($data['dept']);

        if (filled($data['dept'])) {
            abort_unless(Department::where('name', $data['dept'])->exists(), 422, 'Département inconnu.');
        }

        // Un responsable ne peut faire l'appel que de son département
        if ($user->isResponsable() && $data['dept'] !== $user->dept) {
            abort(403, 'Vous ne pouvez prendre les présences que pour votre département.');
        }

        if ($user->isResponsable() && filled($event->dept) && $event->dept !== $user->dept) {
            abort(403, 'Vous ne pouvez enregistrer des présences que pour les événements de votre département.');
        }

        $memberIds = Member::query()
            ->when($data['dept'] === null, fn ($query) => $query->whereNull('dept'))
            ->when(filled($data['dept']), fn ($query) => $query->where('dept', $data['dept']))
            ->pluck('id');

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

        return redirect()->route('attendances.sheet', ['event' => $event, 'dept' => $data['dept'] ?? self::UNASSIGNED_DEPARTMENT])
            ->with('success', 'Présences enregistrées pour les membres sans département.');
    }

    protected function normalizeDepartment(?string $department): ?string
    {
        return $department === self::UNASSIGNED_DEPARTMENT ? null : $department;
    }

    /**
     * Rapport global des présences (secrétariat / admin).
     */
    public function report(Request $request)
    {
        $user = auth()->user();

        abort_if($user->isResponsable(), 403, 'Le rapport global est réservé à l\'administration et au secrétariat.');

        $query = Attendance::query()
            ->join('members', 'members.id', '=', 'attendances.member_id')
            ->join('events', 'events.id', '=', 'attendances.event_id')
            ->select('attendances.*')
            ->with(['member', 'event']);

        if ($request->filled('event_id')) {
            $query->where('attendances.event_id', (int) $request->event_id);
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
            ->reorder()
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
            'events' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where('dept', $user->dept))
                ->orderByDesc('date')
                ->take(30)
                ->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['event_id', 'dept', 'status', 'from', 'to']),
        ]);
    }

    /**
     * Génère le rapport PDF avec les mêmes filtres que l'écran de rapport.
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();

        if ($user->isResponsable()) {
            $request->merge(['dept' => $user->dept]);

            if ($request->filled('event_id')) {
                $allowedEventId = Event::query()
                    ->where(function ($query) use ($user) {
                        $query->whereNull('dept')
                            ->orWhere('dept', $user->dept);
                    })
                    ->whereKey((int) $request->event_id)
                    ->exists();

                abort_unless($allowedEventId, 403, 'Vous ne pouvez exporter que les rapports de votre département, y compris sur les événements globaux.');
            }
        }

        $query = $this->filteredReportQuery($request);
        $rows = $query->orderByDesc('events.date')->orderBy('members.name')->get();
        $summary = $this->reportSummary(clone $query);

        return Pdf::setOption([
            'tempDir' => sys_get_temp_dir(),
            'fontDir' => sys_get_temp_dir(),
            'fontCache' => sys_get_temp_dir(),
        ])->loadView('attendances.pdf', [
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

        if (auth()->user()->isResponsable()) {
            $query->where('members.dept', auth()->user()->dept)
                ->where(function ($query) {
                    $query->whereNull('events.dept')
                        ->orWhere('events.dept', auth()->user()->dept);
                });
        }

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
            ->reorder()
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
