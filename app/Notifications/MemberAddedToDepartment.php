<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberAddedToDepartment extends Notification
{
    use Queueable;

    public function __construct(public Member $member, public string $department)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nouveau membre ajouté',
            'message' => 'Le membre '.$this->member->name.' a été ajouté au département '.$this->department.'.',
            'member_id' => $this->member->id,
            'department' => $this->department,
            'type' => 'member_added',
        ];
    }
}
