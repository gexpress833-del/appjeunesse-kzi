<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\User;
use App\Notifications\AttendanceBatchRecorded;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private const UNASSIGNED_DEPARTMENT = '__none__';

    /**
     * Choix de l'événement pour la prise de présence.
     * Un responsable voit les événements globaux et ceux de son département.
     */
    public function pick(Request $request)
    {
        $user = auth()->user();
        $portal = $request->attributes->get('portal', 'church');

        if ($portal === 'church' && $user->isPastorPrincipal()) {
            $filters = $request->validate([
                'event_id' => ['nullable', 'integer', 'exists:events,id'],
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            ]);

            $attendanceStats = Attendance::query()
                ->join('members', 'members.id', '=', 'attendances.member_id')
                ->join('events', 'events.id', '=', 'attendances.event_id')
                ->where('events.portal', 'church')
                ->select('members.dept')
                ->selectRaw('COUNT(attendances.id) as total,
                    SUM(CASE WHEN attendances.status = \'present\' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN attendances.status = \'late\' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN attendances.status = \'excused\' THEN 1 ELSE 0 END) as excused,
                    SUM(CASE WHEN attendances.status = \'absent\' THEN 1 ELSE 0 END) as absent');

            if (filled($filters['event_id'] ?? null)) {
                $attendanceStats->where('attendances.event_id', $filters['event_id']);
            }

            if (filled($filters['from'] ?? null)) {
                $attendanceStats->where('events.date', '>=', $filters['from'].' 00:00:00');
            }

            if (filled($filters['to'] ?? null)) {
                $attendanceStats->where('events.date', '<=', $filters['to'].' 23:59:59');
            }

            $attendanceStats->groupBy('members.dept');

            $departmentStats = Department::query()
                ->leftJoinSub($attendanceStats, 'attendance_stats', fn ($join) => $join->on('attendance_stats.dept', '=', 'departments.name'))
                ->select('departments.name as department')
                ->selectRaw('COALESCE(attendance_stats.total, 0) as total,
                    COALESCE(attendance_stats.present, 0) as present,
                    COALESCE(attendance_stats.late, 0) as late,
                    COALESCE(attendance_stats.excused, 0) as excused,
                    COALESCE(attendance_stats.absent, 0) as absent')
                ->orderBy('departments.name')
                ->get()
                ->each(fn ($department) => $department->rate = $department->total > 0
                    ? (int) round((($department->present + $department->late) / $department->total) * 100)
                    : 0);

            $events = Event::query()
                ->where('portal', 'church')
                ->whereHas('attendances')
                ->orderByDesc('date')
                ->get(['id', 'name', 'date']);
            $selectedEvent = $events->firstWhere('id', (int) ($filters['event_id'] ?? 0));

            return view('attendances.pastor', compact('departmentStats', 'events', 'filters', 'selectedEvent'));
        }

        $canViewAllDepartments = $portal === 'church' && ($user->isAdmin() || $user->isSecretariat());
        $departments = $canViewAllDepartments
            ? Department::query()->orderBy('name')->get()
            : $user->attendanceDepartments($portal);

        abort_if($departments->isEmpty(), 403, 'Une affectation active au département est requise pour gérer les présences.');
        $departmentNames = $departments->pluck('name');

        return view('attendances.pick', [
            'upcoming' => Event::query()
                ->where('portal', $portal)
                ->when(! $canViewAllDepartments, fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhereIn('dept', $departmentNames)))
                ->upcoming()->take(5)->get(),
            'past' => Event::query()
                ->where('portal', $portal)
                ->when(! $canViewAllDepartments, fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhereIn('dept', $departmentNames)))
                ->past()->take(10)->get(),
            'dept' => $user->isResponsable() && $portal === 'church' ? $user->dept : $request->query('dept'),
            'departments' => $departments,
            'portal' => $portal,
            'canViewAllDepartments' => $canViewAllDepartments,
        ]);
    }

    /**
     * Feuille de présence d'un événement pour un département.
     */
    public function sheet(Request $request, Event $event)
    {
        $user = auth()->user();
        $portal = $request->attributes->get('portal', 'church');

        abort_unless($event->portal === $portal, 403, 'Cet événement appartient à un autre portail.');

        $canViewAllDepartments = $portal === 'church'
            && ($user->isAdmin() || $user->isSecretariat() || $user->isPastorPrincipal());
        $departments = $canViewAllDepartments
            ? Department::query()->orderBy('name')->get()
            : $user->attendanceDepartments($portal);

        if (filled($event->dept)) {
            $departments = $departments->where('name', $event->dept)->values();
        }

        abort_if($departments->isEmpty() && ! $canViewAllDepartments, 403, 'Une affectation active au département est requise pour consulter cette feuille.');

        $requestedDept = $this->normalizeDepartment($request->query('dept'));

        if ($request->missing('dept') && ! $canViewAllDepartments && $departments->count() === 1) {
            $requestedDept = $departments->first()->name;
        }

        $dept = $requestedDept;

        $departmentSelectionRequired = $request->missing('dept') && ($canViewAllDepartments || $departments->count() > 1);

        if (! $canViewAllDepartments) {
            abort_unless($user->canManageAttendance($portal, $dept), 403, 'Vous ne pouvez consulter que les départements qui vous sont affectés dans ce portail.');
        }

        if (filled($dept)) {
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

        $readOnly = ! $user->canManageAttendance($portal, $dept);

        return view('attendances.sheet', compact(
            'event',
            'dept',
            'members',
            'existing',
            'departments',
            'departmentSelectionRequired',
            'readOnly',
            'canViewAllDepartments',
            'portal',
        ));
    }

    /**
     * Enregistrement (upsert) des statuts de présence.
     */
    public function store(Request $request, Event $event)
    {
        $user = auth()->user();
        $portal = $request->attributes->get('portal', 'church');

        abort_unless($event->portal === $portal, 403, 'Cet événement appartient à un autre portail.');

        $data = $request->validate([
            'dept' => ['required', 'string'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['in:'.implode(',', AppSetting::current()->attendance_statuses)],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $data['dept'] = $this->normalizeDepartment($data['dept']);

        if (filled($data['dept'])) {
            abort_unless(Department::where('name', $data['dept'])->exists(), 422, 'Département inconnu.');
        }

        abort_unless($user->canManageAttendance($portal, $data['dept']), 403, 'Vous ne pouvez enregistrer les présences que pour les départements qui vous sont affectés dans ce portail.');

        if (filled($event->dept) && $event->dept !== $data['dept']) {
            abort(403, 'Cet événement est réservé à un autre département.');
        }

        $memberIds = Member::query()
            ->when($data['dept'] === null, fn ($query) => $query->whereNull('dept'))
            ->when(filled($data['dept']), fn ($query) => $query->where('dept', $data['dept']))
            ->pluck('id');

        $recordedCount = 0;

        foreach ($data['statuses'] as $memberId => $status) {
            $memberId = (int) $memberId;

            if (! $memberIds->contains($memberId)) {
                continue; // pas un membre du département concerné
            }

            $existingAttendance = Attendance::where('member_id', $memberId)
                ->where('event_id', $event->id)
                ->first();
            $editableHours = AppSetting::current()->attendance_editable_hours;

            if ($existingAttendance && $editableHours > 0 && $existingAttendance->created_at?->lt(now()->subHours($editableHours))) {
                continue;
            }

            $attendance = Attendance::updateOrCreate(
                ['member_id' => $memberId, 'event_id' => $event->id],
                ['status' => $status, 'notes' => $data['notes'][$memberId] ?? null]
            );

            $recordedCount++;
        }

        if ($recordedCount > 0) {
            $department = $data['dept'] ?? 'Sans département';
            if ($portal === 'youth') {
                $departmentId = Department::query()->where('name', $data['dept'])->value('id');
                $youthPortalId = Department::query()->where('code', 'youth')->value('id');
                $scopeIds = collect([$departmentId, $youthPortalId])->filter()->unique();
                $recipients = User::query()
                    ->whereIn('id', MemberRoleAssignment::query()
                        ->where('scope_type', 'youth')
                        ->where('status', 'active')
                        ->whereIn('scope_id', $scopeIds)
                        ->whereHas('role', fn ($query) => $query->whereIn('slug', ['responsable_jeunesse', 'leader_youth', 'animateur_jeunesse']))
                        ->pluck('user_id'))
                    ->get();
            } else {
                $recipients = User::query()
                    ->whereIn('role', ['admin', 'secretariat'])
                    ->orWhere(fn ($query) => $query
                        ->where('role', 'responsable')
                        ->where('dept', $data['dept']))
                    ->get();
            }

            $recipients = $recipients->reject(fn (User $recipient): bool => $recipient->is($user));

            foreach ($recipients as $recipient) {
                $recipient->notify(new AttendanceBatchRecorded($event, $user, $department, $recordedCount));
            }
        }

        $sheetRoute = $portal === 'youth' ? 'youth.attendances.sheet' : 'attendances.sheet';

        return redirect()->route($sheetRoute, ['event' => $event, 'dept' => $data['dept'] ?? self::UNASSIGNED_DEPARTMENT])
            ->with('success', 'Présences enregistrées.');
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
            ->where('events.portal', 'church')
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

        $rows = $query
            ->orderByDesc('events.date')
            ->orderBy('members.dept')
            ->orderByRaw("CASE WHEN LOWER(COALESCE(members.role, '')) LIKE '%responsable%' THEN 0 ELSE 1 END")
            ->orderBy('members.name')
            ->paginate(50)
            ->withQueryString();

        $eventHistory = Event::query()
            ->whereHas('attendances')
            ->when($request->filled('event_id'), fn ($eventQuery) => $eventQuery->whereKey((int) $request->event_id))
            ->when($request->filled('from'), fn ($eventQuery) => $eventQuery->where('date', '>=', $request->from.' 00:00:00'))
            ->when($request->filled('to'), fn ($eventQuery) => $eventQuery->where('date', '<=', $request->to.' 23:59:59'))
            ->withCount(['attendances as recorded_attendances' => function ($attendanceQuery) use ($request) {
                $attendanceQuery
                    ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                    ->when($request->filled('dept'), fn ($query) => $query->whereHas('member', fn ($memberQuery) => $memberQuery->where('dept', $request->dept)));
            }])
            ->orderByDesc('date')
            ->take(30)
            ->get();

        // Résumé par département sur la sélection courante
        $summaryQuery = clone $query;
        $summary = (clone $summaryQuery)
            ->reorder()
            ->select([])
            ->selectRaw('members.dept, COUNT(*) as total,
                SUM(CASE WHEN attendances.status = \'present\' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = \'late\' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN attendances.status = \'excused\' THEN 1 ELSE 0 END) as excused,
                SUM(CASE WHEN attendances.status = \'absent\' THEN 1 ELSE 0 END) as absent')
            ->groupBy('members.dept')
            ->orderBy('members.dept')
            ->get()
            ->each(fn ($r) => $r->rate = $r->total > 0 ? (int) round((($r->present + $r->late) / $r->total) * 100) : 0);

        $overallTotal = (int) $summary->sum('total');
        $overallPresent = (int) $summary->sum('present');
        $overallLate = (int) $summary->sum('late');
        $overallExcused = (int) $summary->sum('excused');
        $overallAbsent = (int) $summary->sum('absent');
        $overall = [
            'total' => $overallTotal,
            'present' => $overallPresent,
            'late' => $overallLate,
            'excused' => $overallExcused,
            'absent' => $overallAbsent,
            'rate' => $overallTotal > 0 ? (int) round((($overallPresent + $overallLate) / $overallTotal) * 100) : 0,
        ];

        return view('attendances.report', [
            'rows' => $rows,
            'summary' => $summary,
            'overall' => $overall,
            'events' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where('dept', $user->dept))
                ->orderByDesc('date')
                ->take(30)
                ->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['event_id', 'dept', 'status', 'from', 'to']),
            'eventHistory' => $eventHistory,
        ]);
    }

    /**
     * Génère le rapport PDF avec les mêmes filtres que l'écran de rapport.
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $portal = $request->attributes->get('portal', 'church');
        abort_unless(in_array($portal, ['church', 'youth'], true), 404);

        $selectedEvent = $request->filled('event_id')
            ? Event::query()->where('portal', $portal)->findOrFail((int) $request->event_id)
            : null;
        $accessibleDepartmentNames = [];

        if ($selectedEvent && filled($selectedEvent->dept)) {
            abort_unless(blank($request->input('dept')) || $request->input('dept') === $selectedEvent->dept, 403, 'Le département demandé ne correspond pas à celui de l’événement.');
            $request->merge(['dept' => $selectedEvent->dept]);
        }

        if ($portal === 'church' && $user->isResponsable()) {
            $request->merge(['dept' => $user->dept]);

            if ($selectedEvent) {
                abort_unless(blank($selectedEvent->dept) || $selectedEvent->dept === $user->dept, 403, 'Vous ne pouvez exporter que les rapports de votre département, y compris sur les événements globaux.');
            }
        }

        if ($portal === 'youth' && ! $user->isAdmin()) {
            $accessibleDepartmentNames = $user->attendanceDepartments('youth')->pluck('name')->all();
            abort_if($accessibleDepartmentNames === [], 403, 'Une affectation active au département est requise pour exporter ce rapport.');

            if ($selectedEvent && filled($selectedEvent->dept)) {
                abort_unless(in_array($selectedEvent->dept, $accessibleDepartmentNames, true), 403, 'Cet événement appartient à un département hors de votre périmètre.');
                $request->merge(['dept' => $request->input('dept', $selectedEvent->dept)]);
            }

            if ($request->filled('dept')) {
                abort_unless(in_array($request->input('dept'), $accessibleDepartmentNames, true), 403, 'Vous ne pouvez exporter que les rapports de vos départements.');
            } elseif (count($accessibleDepartmentNames) === 1) {
                $request->merge(['dept' => $accessibleDepartmentNames[0]]);
            }
        }

        $query = $this->filteredReportQuery($request, $portal, $user, $accessibleDepartmentNames);
        $rows = $query
            ->orderByDesc('events.date')
            ->orderBy('members.dept')
            ->orderByRaw("CASE WHEN LOWER(COALESCE(members.role, '')) LIKE '%responsable%' THEN 0 ELSE 1 END")
            ->orderBy('members.name')
            ->get();
        $summary = $this->reportSummary(clone $query);
        $departmentName = $selectedEvent?->dept ?: ($request->input('dept') ?: 'Tous les départements');
        $portalName = match ($portal) {
            'church' => 'Église',
            'youth' => 'Jeunesse',
            default => 'ECODIM',
        };
        $portalLabel = match ($portal) {
            'church' => 'PORTAIL ÉGLISE',
            'youth' => 'PORTAIL JEUNESSE',
            default => 'PORTAIL ECODIM',
        };
        $reportScope = $departmentName === 'Tous les départements'
            ? $portalName.' - Tous les départements'
            : $portalName.' - Département '.$departmentName;
        $responsibleName = $selectedEvent
            ? (User::query()->where('username', $selectedEvent->created_by)->value('full_name') ?: ($selectedEvent->created_by ?: 'Non renseigné'))
            : 'Selon événement';

        return Pdf::setOption([
            'tempDir' => sys_get_temp_dir(),
            'fontDir' => sys_get_temp_dir(),
            'fontCache' => sys_get_temp_dir(),
            'isRemoteEnabled' => true,
        ])->loadView('attendances.pdf', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $request->only(['event_id', 'dept', 'status', 'from', 'to']),
            'generatedAt' => now(),
            'portal' => $portal,
            'portalLabel' => $portalLabel,
            'portalColor' => match ($portal) {
                'church' => 'church',
                'youth' => 'youth',
                default => 'ecodim',
            },
            'reportScope' => $reportScope,
            'eventName' => $selectedEvent?->name ?? 'Tous les événements',
            'eventDate' => $selectedEvent?->date?->translatedFormat('d F Y') ?? 'Selon période sélectionnée',
            'departmentName' => $departmentName,
            'responsibleName' => $responsibleName,
        ])->setPaper('a4', 'landscape')->download('rapport-presences-'.now()->format('Y-m-d').'.pdf');
    }

    protected function filteredReportQuery(Request $request, string $portal, User $user, array $accessibleDepartmentNames = [])
    {
        $query = Attendance::query()
            ->join('members', 'members.id', '=', 'attendances.member_id')
            ->join('events', 'events.id', '=', 'attendances.event_id')
            ->where('events.portal', $portal)
            ->select('attendances.*')
            ->with(['member', 'event']);

        if ($portal === 'church' && $user->isResponsable()) {
            $query->where('members.dept', $user->dept)
                ->where(function ($query) use ($user) {
                    $query->whereNull('events.dept')
                        ->orWhere('events.dept', $user->dept);
                });
        }

        if ($portal === 'youth' && ! $user->isAdmin()) {
            $query->whereIn('members.dept', $accessibleDepartmentNames)
                ->where(function ($eventQuery) use ($accessibleDepartmentNames) {
                    $eventQuery->whereNull('events.dept')
                        ->orWhereIn('events.dept', $accessibleDepartmentNames);
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
            ->select([])
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
