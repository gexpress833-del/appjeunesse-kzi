<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountValidated extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Compte validé',
            'message' => 'Votre compte est maintenant actif. Vous pouvez accéder à votre espace membre.',
            'type' => 'account_validated',
        ];
    }
}
