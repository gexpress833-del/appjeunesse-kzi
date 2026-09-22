<?php

namespace App\Notifications;

use App\Models\SocialVisit;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SocialVisitAssigned extends Notification
{
    use Queueable;

    public function __construct(public SocialVisit $visit) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Suivi social affecté',
            'message' => 'Un suivi pour '.$this->visit->member->name.' vous a été affecté le '.$this->visit->visit_date->translatedFormat('d/m/Y').'.',
            'type' => 'social_visit_assigned',
            'social_visit_id' => $this->visit->id,
            'member_id' => $this->visit->member_id,
        ];
    }
}
