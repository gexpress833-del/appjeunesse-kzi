<?php

namespace App\Http\Controllers;

use App\Models\UserFcmToken;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(DatabaseNotification $notification)
    {
        if ($notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== auth()->user()::class) {
            abort(403);
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function destroy(DatabaseNotification $notification)
    {
        if ($notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== auth()->user()::class) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('notifications', []);

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'Aucune notification sélectionnée.');
        }

        auth()->user()->notifications()->whereIn('id', $ids)->delete();

        return back()->with('success', 'Notifications supprimées.');
    }

    public function markAllAsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }

    public function registerFcmToken(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'max:1024'],
            'device' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        UserFcmToken::registerForUser($user, $request->string('token')->toString(), $request->input('device', 'web'));

        return response()->json([
            'status' => 'registered',
            'message' => 'Token FCM enregistré.',
        ]);
    }

    public function sendTestPush(Request $request)
    {
        $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:240'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $title = $request->input('title', 'Test de notification');
        $body = $request->input('body', 'Ceci est une notification de test FCM.');

        $this->sendPushToUser($user, $title, $body, [
            'type' => 'test',
            'click_action' => '/notifications',
        ]);

        return response()->json([
            'status' => 'queued',
            'message' => 'Notification de test envoyée.',
        ]);
    }

    public function sendPushToUser($user, string $title, string $body, array $data = []): void
    {
        $tokens = UserFcmToken::query()
            ->where('user_id', $user->getKey())
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
            $messaging = Firebase::messaging();

            foreach (array_chunk($tokens, 500) as $batch) {
                $message = CloudMessage::fromArray([
                    'token' => $batch[0],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_merge([
                        'title' => $title,
                        'body' => $body,
                    ], $data),
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                        ],
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                            'icon' => '/logoEglise.jpg',
                            'badge' => '/logoEglise.jpg',
                        ],
                    ],
                ]);

                $messaging->sendMulticast($message, $batch);
            }
        } catch (\Throwable $exception) {
            Log::error('FCM delivery failed: '.$exception->getMessage());
        }
    }
}
