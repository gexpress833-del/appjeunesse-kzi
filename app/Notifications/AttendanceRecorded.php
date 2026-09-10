<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceRecorded extends Notification
{
    use Queueable;

    public function __construct(public Attendance $attendance, public Event $event, public User $recordedBy)
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
            'title' => 'Présence enregistrée',
            'message' => $this->recordedBy->full_name.' a enregistré la présence pour “'.$this->event->name.'”.',
            'type' => 'attendance_recorded',
            'event_id' => $this->event->id,
            'member_id' => $this->attendance->member_id,
            'status' => $this->attendance->status,
        ];
    }
}
