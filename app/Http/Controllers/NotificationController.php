<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

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
}
