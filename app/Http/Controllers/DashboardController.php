<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\EcodimClass;
use App\Models\EcodimTransition;
use App\Models\Event;
use App\Models\HomeContent;
use App\Models\Member;
use App\Models\Photo;
use App\Models\SocialVisit;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tableau de bord adapté au rôle :
     * - user          : progression personnelle (présences/participations)
     * - responsable   : progression personnelle + son département
     * - secretariat/admin : statistiques globales de la jeunesse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $portal = $user->currentPortal();

        if ($user->canGovernPortal($portal)) {
            return $this->governance($portal);
        }

        if ($portal === 'church' && $user->isChurchAdministrator()) {
            return $this->global();
        }

        if (! $user->hasOperationalPortalAccess($portal)) {
            return view('dashboard.informational', [
                'portal' => $portal,
                'announcements' => HomeContent::query()
                    ->where('source', $portal)
                    ->whereIn('type', ['verset', 'temoignage', 'event_banner'])
                    ->active()
                    ->ordered()
                    ->get(),
            ]);
        }

        return $this->personal($user);
    }

    public function bilan(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isSecretariat()) {
            return redirect()->route('dashboard');
        }

        $member = $user->member()->first();

        if (! $member) {
            return view('dashboard.bilan', [
                'member' => null,
                'stats' => [],
                'recent' => collect(),
                'visits' => collect(),
            ]);
        }

        $stats = [
            'total' => Attendance::where('member_id', $member->id)->count(),
            'present' => Attendance::where('member_id', $member->id)->where('status', 'present')->count(),
            'late' => Attendance::where('member_id', $member->id)->where('status', 'late')->count(),
            'excused' => Attendance::where('member_id', $member->id)->where('status', 'excused')->count(),
            'absent' => Attendance::where('member_id', $member->id)->where('status', 'absent')->count(),
        ];

        $stats['rate'] = $stats['total'] > 0
            ? (int) round((($stats['present'] + $stats['late']) / $stats['total']) * 100)
            : null;

        return view('dashboard.bilan', [
            'member' => $member->load('department'),
            'stats' => $stats,
            'visits' => SocialVisit::with('assignee')
                ->where('member_id', $member->id)
                ->latest('visit_date')->take(5)->get(),
            'recent' => Attendance::with('event')
                ->where('member_id', $member->id)
                ->latest('id')->take(6)->get(),
        ]);
    }

    public function announcements(Request $request)
    {
        $portal = (string) $request->attributes->get('portal');

        return view('dashboard.announcements', [
            'portal' => $portal,
            'announcements' => HomeContent::query()
                ->where('source', $portal)
                ->whereIn('type', ['verset', 'temoignage', 'event_banner'])
                ->active()
                ->ordered()
                ->get(),
        ]);
    }

    protected function governance(string $portal)
    {
        $summary = match ($portal) {
            'ecodim' => [
                'classes' => EcodimClass::query()->where('status', 'active')->count(),
                'children' => Member::query()->whereHas('memberships', fn ($query) => $query
                    ->where('type', 'ecodim')->where('status', 'active'))->count(),
                'transitions' => EcodimTransition::query()->count(),
            ],
            'youth' => [
                'members' => Member::query()->whereHas('memberships', fn ($query) => $query
                    ->where('type', 'youth')->where('status', 'active'))->count(),
                'events' => Event::query()->where('portal', 'youth')->count(),
            ],
            default => [
                'members' => Member::query()->count(),
                'users' => User::query()->where('status', 'active')->count(),
                'departments' => Department::query()->count(),
            ],
        };

        return view('dashboard.portal-governance', [
            'portal' => $portal,
            'summary' => $summary,
        ]);
    }

    protected function global()
    {
        $lastEvents = Event::query()->where('portal', 'church')->past()->take(5)->withCount('members')->get();

        return view('dashboard.global', [
            'membersCount' => Member::count(),
            'usersCount' => User::where('status', 'active')->count(),
            'pendingCount' => User::where('status', 'pending')->count(),
            'photosCount' => Photo::count(),
            'upcoming' => Event::query()->where('portal', 'church')->upcoming()->take(3)->get(),
            'lastEvents' => $lastEvents,
            'deptStats' => $this->deptPresenceRates($lastEvents->pluck('id')),
            'departments' => Department::query()->with('leader')->withCount('members')->orderBy('name')->get(),
            'recentAnnouncements' => HomeContent::query()
                ->where('source', 'church')
                ->whereIn('type', ['verset', 'temoignage', 'event_banner'])
                ->active()
                ->latest('updated_at')
                ->take(4)
                ->get(),
        ]);
    }

    protected function personal($user)
    {
        $member = $user->member()->first();
        $portal = $user->currentPortal();

        $departmentMembersCount = $user->dept ? Member::where('dept', $user->dept)->count() : 0;

        if (! $member) {
            return view('dashboard.personal', [
                'member' => null,
                'stats' => [],
                'visits' => collect(),
                'recent' => collect(),
                'upcoming' => Event::query()
                    ->where('portal', $portal)
                    ->when($user->isResponsable(), fn ($query) => $query->where('dept', $user->dept))
                    ->upcoming()->take(3)->get(),
                'announcements' => HomeContent::where('source', $portal)
                    ->whereIn('type', ['verset', 'temoignage', 'event_banner'])
                    ->active()->ordered()->take(4)->get(),
                'departmentMembersCount' => $departmentMembersCount,
            ]);
        }

        $stats = [
            'total' => Attendance::where('member_id', $member->id)->count(),
            'present' => Attendance::where('member_id', $member->id)->where('status', 'present')->count(),
            'late' => Attendance::where('member_id', $member->id)->where('status', 'late')->count(),
            'excused' => Attendance::where('member_id', $member->id)->where('status', 'excused')->count(),
            'absent' => Attendance::where('member_id', $member->id)->where('status', 'absent')->count(),
        ];

        $stats['rate'] = $stats['total'] > 0
            ? (int) round((($stats['present'] + $stats['late']) / $stats['total']) * 100)
            : null;

        return view('dashboard.personal', [
            'member' => $member->load('department'),
            'stats' => $stats,
            'visits' => SocialVisit::with('assignee')
                ->where('member_id', $member->id)
                ->latest('visit_date')->take(5)->get(),
            'recent' => Attendance::with('event')
                ->where('member_id', $member->id)
                ->latest('id')->take(6)->get(),
            'upcoming' => Event::query()
                ->where('portal', $portal)
                ->when($user->isResponsable(), fn ($query) => $query->where('dept', $user->dept))
                ->upcoming()->take(3)->get(),
            'announcements' => HomeContent::where('source', $portal)
                ->whereIn('type', ['verset', 'temoignage', 'event_banner'])
                ->active()->ordered()->take(4)->get(),
            'departmentMembersCount' => $departmentMembersCount,
        ]);
    }

    /**
     * Taux de présence (présent + en retard) par département sur les
     * derniers événements.
     */
    protected function deptPresenceRates($eventIds)
    {
        return Member::query()
            ->selectRaw('members.dept, COUNT(attendances.id) as total,
                SUM(CASE WHEN attendances.status IN (\'present\',\'late\') THEN 1 ELSE 0 END) as ok')
            ->leftJoin('attendances', function ($join) use ($eventIds) {
                $join->on('attendances.member_id', '=', 'members.id')
                    ->whereIn('attendances.event_id', $eventIds);
            })
            ->groupBy('members.dept')
            ->orderBy('members.dept')
            ->get()
            ->map(function ($row) {
                $row->rate = $row->total > 0 ? (int) round(($row->ok / $row->total) * 100) : null;

                return $row;
            });
    }
}
