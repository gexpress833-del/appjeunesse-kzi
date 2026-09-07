<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $apiKey = config('services.brevo.api_key');
        $email = $notifiable->routeNotificationFor('brevo')
            ?? $notifiable->routeNotificationFor('mail');

        if (blank($apiKey) || blank($email) || ! method_exists($notification, 'toBrevo')) {
            return;
        }

        $message = $notification->toBrevo($notifiable);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $apiKey,
            'content-type' => 'application/json',
        ])->timeout(10)->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'email' => config('services.brevo.sender_email'),
                'name' => config('services.brevo.sender_name'),
            ],
            'to' => [[
                'email' => $email,
                'name' => $notifiable->full_name ?? $email,
            ]],
            'subject' => $message['subject'],
            'htmlContent' => $message['html'],
        ]);

        if ($response->failed()) {
            Log::error('Brevo email delivery failed.', [
                'recipient' => $email,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }
    }
}
