<?php

namespace App\Notifications;

use App\Notifications\Channels\BrevoChannel;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PasswordResetRequested extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return [BrevoChannel::class, FcmChannel::class];
    }

    /**
     * @return array{subject: string, html: string}
     */
    public function toBrevo(object $notifiable): array
    {
        $senderName = config('services.brevo.sender_name', config('app.name', 'AETERNA LINKS'));

        return [
            'subject' => sprintf('Réinitialisation de votre mot de passe %s', $senderName),
            'html' => view('emails.password-reset', [
                'user' => $notifiable,
                'resetUrl' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->email,
                ]),
            ])->render(),
        ];
    }
}
