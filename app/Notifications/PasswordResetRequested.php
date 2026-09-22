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
        return [
            'subject' => 'Réinitialisation de votre mot de passe appjeunesse-kzi',
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
