<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountValidated extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', BrevoChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Compte validé',
            'message' => 'Votre compte est maintenant actif. Vous pouvez accéder à votre espace membre.',
            'type' => 'account_validated',
        ];
    }

    /**
     * @return array{subject: string, html: string}
     */
    public function toBrevo(object $notifiable): array
    {
        return [
            'subject' => 'Votre compte appjeunesse-kzi est validé',
            'html' => view('emails.account-validated', [
                'user' => $notifiable,
            ])->render(),
        ];
    }
}
