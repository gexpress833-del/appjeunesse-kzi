<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\EventCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmNotificationChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_notifications_use_firebase_push_channel(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'name' => 'Événement test',
            'date' => now()->addDay(),
            'created_by' => $user->username,
        ]);

        $notification = new EventCreated($event, $user);

        $this->assertContains('database', $notification->via($user));
        $this->assertContains(FcmChannel::class, $notification->via($user));
    }
}
