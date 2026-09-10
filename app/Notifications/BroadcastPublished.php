<?php

namespace App\Notifications;

use App\Models\HomeContent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BroadcastPublished extends Notification
{
    use Queueable;

    public function __construct(public HomeContent $broadcast) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isReplay = $this->broadcast->broadcast_type === 'replay';
        $label = $isReplay ? 'Retransmission disponible' : 'Direct disponible';

        return [
            'title' => $label,
            'message' => ($this->broadcast->title ?: 'Culte de la jeunesse').' est maintenant disponible.',
            'type' => $isReplay ? 'replay_published' : 'live_published',
            'media_url' => $this->broadcast->media_url,
        ];
    }
}
