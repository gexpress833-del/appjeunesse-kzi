<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttendanceBatchRecorded extends Notification
{
    use Queueable;

    public function __construct(
        public Event $event,
        public User $recordedBy,
        public string $department,
        public int $memberCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Présences enregistrées',
            'message' => $this->recordedBy->full_name.' a enregistré '.$this->memberCount.' présence(s) pour “'.$this->event->name.'” · '.$this->department.'.',
            'type' => 'attendance_batch_recorded',
            'event_id' => $this->event->id,
            'department' => $this->department,
            'member_count' => $this->memberCount,
        ];
    }
}
