<?php

namespace App\Notifications\Channels;

use App\Models\UserFcmToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalChannel
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

        $appId = config('services.onesignal.app_id');
        $restApiKey = config('services.onesignal.rest_api_key');

        if (blank($appId) || blank($restApiKey)) {
            Log::warning('OneSignal credentials are missing. Push notification not sent.');

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$restApiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $appId,
                'include_player_ids' => $tokens,
                'headings' => [
                    'fr' => (string) $payload['title'],
                    'en' => (string) $payload['title'],
                ],
                'contents' => [
                    'fr' => (string) $payload['message'],
                    'en' => (string) $payload['message'],
                ],
                'data' => array_merge([
                    'title' => (string) $payload['title'],
                    'body' => (string) $payload['message'],
                    'click_action' => $payload['click_action'] ?? '/notifications',
                    'type' => (string) ($payload['type'] ?? 'notification'),
                ], $this->normalizeData($payload)),
                'web_url' => url($payload['click_action'] ?? '/notifications'),
                'chrome_web_icon' => url('/logoEglise.jpg'),
                'chrome_web_badge' => url('/logoEglise.jpg'),
            ]);

            if (! $response->successful()) {
                Log::warning('OneSignal delivery failed: '.$response->body());

                return;
            }

            $invalidIds = $response->json('invalid_player_ids', []);

            foreach ($invalidIds as $invalidId) {
                UserFcmToken::query()->where('token', $invalidId)->delete();
            }
        } catch (\Throwable $exception) {
            Log::error('OneSignal delivery failed: '.$exception->getMessage());
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
