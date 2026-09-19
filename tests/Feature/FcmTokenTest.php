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

        $response = $this->actingAs($user)->postJson('/notifications/fcm/register', [
            'token' => 'test-fcm-token-123',
            'device' => 'web',
        ]);

        $response
            ->assertOk()
            ->assertJson(['status' => 'registered']);

        $this->assertDatabaseHas('user_fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'test-fcm-token-123',
            'device' => 'web',
        ]);
    }

    public function test_unauthenticated_user_cannot_register_push_token(): void
    {
        $this->postJson('/notifications/fcm/register', [
            'token' => 'test-fcm-token-123',
            'device' => 'web',
        ])->assertUnauthorized();
    }
}
