<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventCreated extends Notification
{
    use Queueable;

    public function __construct(public Event $event, public User $createdBy)
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
            'title' => 'Nouvel événement',
            'message' => 'L’événement “'.$this->event->name.'” a été ajouté par '.$this->createdBy->full_name.'.',
            'type' => 'event_created',
            'event_id' => $this->event->id,
            'department' => $this->event->dept,
        ];
    }
}
