<?php

namespace App\Policies;

use App\Models\EcodimClass;
use App\Models\User;

class EcodimClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canGovernPortal('ecodim') || $user->canUsePortal('ecodim', 'ecodim.members.view');
    }

    public function viewAttendance(User $user, EcodimClass $class): bool
    {
        return $user->canGovernPortal('ecodim')
            || $user->canUsePortal('ecodim', 'ecodim.attendance.manage', $class);
    }

    public function manageAttendance(User $user, EcodimClass $class): bool
    {
        return $user->canUsePortal('ecodim', 'ecodim.attendance.manage', $class);
    }

    public function manage(User $user, EcodimClass $class): bool
    {
        return $user->canGovernPortal('ecodim')
            || $user->canUsePortal('ecodim', 'ecodim.classes.manage', $class);
    }

    public function create(User $user): bool
    {
        return $user->canGovernPortal('ecodim') || $user->canUsePortal('ecodim', 'ecodim.classes.manage');
    }
}
