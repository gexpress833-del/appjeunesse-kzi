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
        return $this->registerOneSignalToken($request);
    }

    public function registerOneSignalToken(Request $request)
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
            'message' => 'Token OneSignal enregistré.',
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

        $result = $this->sendPushToUser($user, $title, $body, [
            'type' => 'test',
            'click_action' => '/notifications',
        ]);

        if ($result['tokens'] === 0) {
            return response()->json([
                'status' => 'not_registered',
                'message' => 'Aucun navigateur FCM enregistré pour ce compte.',
            ], 422);
        }

        if ($result['sent'] === 0) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Firebase a refusé l’envoi. Consultez les logs Render.',
            ], 502);
        }

        return response()->json([
            'status' => 'sent',
            'message' => 'Notification de test envoyée à '.$result['sent'].' navigateur(s).',
        ]);
    }

    /**
     * @return array{tokens: int, sent: int, failed: int}
     */
    public function sendPushToUser($user, string $title, string $body, array $data = []): array
    {
        $tokens = UserFcmToken::query()
            ->where('user_id', $user->getKey())
            ->whereNotNull('token')
            ->pluck('token')
            ->all();

        if ($tokens === []) {
            return ['tokens' => 0, 'sent' => 0, 'failed' => 0];
        }

        $credentials = config('firebase.projects.app.credentials') ?? config('services.firebase.credentials');

        if (blank($credentials)) {
            Log::warning('Firebase credentials are missing. Push notification not sent.');

            return ['tokens' => count($tokens), 'sent' => 0, 'failed' => count($tokens)];
        }

        $sent = 0;
        $failed = 0;

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

                $report = $messaging->sendMulticast($message, $batch);
                $sent += count($report->successes());
                $failed += count($report->failures());

                foreach ($report->invalidTokens() as $invalidToken) {
                    UserFcmToken::query()->where('token', $invalidToken)->delete();
                }
            }
        } catch (\Throwable $exception) {
            Log::error('FCM delivery failed: '.$exception->getMessage());
            $failed = count($tokens);
        }

        return ['tokens' => count($tokens), 'sent' => $sent, 'failed' => $failed];
    }
}
