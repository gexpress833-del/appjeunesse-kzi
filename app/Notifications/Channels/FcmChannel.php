<?php

namespace App\Notifications\Channels;

use App\Models\UserFcmToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notifiable, 'getKey')) {
            return;
        }

        $payload = method_exists($notification, 'toDatabase')
            ? $notification->toDatabase($notifiable)
            : [];

        if (! is_array($payload) || blank($payload['title'] ?? null) || blank($payload['message'] ?? null)) {
            return;
        }

        $tokens = UserFcmToken::query()
            ->where('user_id', $notifiable->getKey())
            ->whereNotNull('token')
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return;
        }

        $credentials = config('firebase.projects.app.credentials') ?? config('services.firebase.credentials');

        if (blank($credentials)) {
            Log::warning('Firebase credentials are missing. Push notification not sent.');

            return;
        }

        try {
            $message = CloudMessage::fromArray([
                'notification' => [
                    'title' => (string) $payload['title'],
                    'body' => (string) $payload['message'],
                ],
                'data' => array_merge([
                    'title' => (string) $payload['title'],
                    'body' => (string) $payload['message'],
                    'click_action' => $payload['click_action'] ?? '/notifications',
                    'type' => (string) ($payload['type'] ?? 'notification'),
                ], $this->normalizeData($payload)),
                'webpush' => [
                    'headers' => ['Urgency' => 'high'],
                    'notification' => [
                        'title' => (string) $payload['title'],
                        'body' => (string) $payload['message'],
                        'icon' => '/logoEglise.jpg',
                        'badge' => '/logoEglise.jpg',
                    ],
                ],
            ]);

            $report = Firebase::messaging()->sendMulticast($message, $tokens);

            foreach ($report->invalidTokens() as $invalidToken) {
                UserFcmToken::query()->where('token', $invalidToken)->delete();
            }
        } catch (\Throwable $exception) {
            Log::error('FCM delivery failed: '.$exception->getMessage());
        }
    }

    protected function normalizeData(array $payload): array
    {
        $data = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['title', 'message', 'click_action'], true)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_THROW_ON_ERROR);
            }

            $data[(string) $key] = is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value);
        }

        return $data;
    }
}
