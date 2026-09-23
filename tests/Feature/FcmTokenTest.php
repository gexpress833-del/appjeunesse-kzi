<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_push_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/notifications/onesignal/register', [
            'token' => 'test-onesignal-player-id-123',
            'device' => 'web',
        ]);

        $response
            ->assertOk()
            ->assertJson(['status' => 'registered']);

        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'test-onesignal-player-id-123',
            'device' => 'web',
        ]);
    }

    public function test_unauthenticated_user_cannot_register_push_token(): void
    {
        $this->postJson('/notifications/onesignal/register', [
            'token' => 'test-onesignal-player-id-123',
            'device' => 'web',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_send_test_push_via_onesignal(): void
    {
        config()->set('services.onesignal.app_id', 'test-app-id');
        config()->set('services.onesignal.rest_api_key', 'test-rest-key');

        $user = User::factory()->create();

        $user->fcmTokens()->create([
            'token' => 'test-onesignal-player-id-456',
            'device' => 'web',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://onesignal.com/api/v1/notifications' => \Illuminate\Support\Facades\Http::response(['id' => 'notification-id'], 200),
        ]);

        $response = $this->actingAs($user)->postJson('/notifications/onesignal/test', [
            'title' => 'Test OneSignal',
            'body' => 'Message de test depuis OneSignal',
        ]);

        $response
            ->assertOk()
            ->assertJson(['status' => 'sent']);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->url() === 'https://onesignal.com/api/v1/notifications'
                && $request['app_id'] === 'test-app-id'
                && $request['include_player_ids'][0] === 'test-onesignal-player-id-456';
        });
    }
}
