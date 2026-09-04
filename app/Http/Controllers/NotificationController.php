<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }
}
