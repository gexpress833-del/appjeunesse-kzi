<?php

namespace App\Policies;

use App\Models\EcodimClass;
use App\Models\User;

class EcodimClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasEcodimPermission('ecodim.members.view');
    }

    public function viewAttendance(User $user, EcodimClass $class): bool
    {
        return $user->hasEcodimClassPermission($class, 'ecodim.attendance.manage');
    }

    public function manageAttendance(User $user, EcodimClass $class): bool
    {
        return $user->hasEcodimClassPermission($class, 'ecodim.attendance.manage');
    }

    public function manage(User $user, EcodimClass $class): bool
    {
        return $user->hasEcodimClassPermission($class, 'ecodim.classes.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasEcodimPermission('ecodim.classes.manage');
    }
}
