<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountValidated extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', BrevoChannel::class, FcmChannel::class];
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
        $senderName = config('services.brevo.sender_name', config('app.name', 'AETERNA LINKS'));

        return [
            'subject' => sprintf('Votre compte %s est validé', $senderName),
            'html' => view('emails.account-validated', [
                'user' => $notifiable,
            ])->render(),
        ];
    }
}
