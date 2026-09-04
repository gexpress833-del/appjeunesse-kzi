<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\HomeContent;
use App\Models\Member;
use App\Models\Photo;
use App\Models\SocialVisit;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Tableau de bord adapté au rôle :
     * - user          : progression personnelle (présences/participations)
     * - responsable   : progression personnelle + son département
     * - secretariat/admin : statistiques globales de la jeunesse
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSecretariat()) {
            return $this->global();
        }

        return $this->personal($user);
    }

    public function bilan()
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isSecretariat()) {
            return redirect()->route('dashboard');
        }

        $member = $user->member();

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

    protected function global()
    {
        $lastEvents = Event::past()->take(5)->withCount('members')->get();

        return view('dashboard.global', [
            'membersCount' => Member::count(),
            'usersCount' => User::where('status', 'active')->count(),
            'pendingCount' => User::where('status', 'pending')->count(),
            'photosCount' => Photo::count(),
            'upcoming' => Event::upcoming()->take(3)->get(),
            'lastEvents' => $lastEvents,
            'deptStats' => $this->deptPresenceRates($lastEvents->pluck('id')),
        ]);
    }

    protected function personal($user)
    {
        $member = $user->member();

        if (! $member) {
            return view('dashboard.personal', [
                'member' => null,
                'stats' => [],
                'visits' => collect(),
                'recent' => collect(),
                'upcoming' => Event::upcoming()->take(3)->get(),
                'announcements' => HomeContent::whereIn('type', ['verset', 'temoignage', 'event_banner'])
                    ->active()->ordered()->take(4)->get(),
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
            'upcoming' => Event::upcoming()->take(3)->get(),
            'announcements' => HomeContent::whereIn('type', ['verset', 'temoignage', 'event_banner'])
                ->active()->ordered()->take(4)->get(),
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
