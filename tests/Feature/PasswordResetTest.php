<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_email_through_brevo(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.sender_email' => 'appjeunessekzi@gmail.com',
            'services.brevo.sender_name' => 'appjeunesse-kzi',
        ]);
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'reset-message'], 201)]);

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('success');

        Http::assertSent(function ($request) use ($user): bool {
            return data_get($request->data(), 'to.0.email') === $user->email
                && data_get($request->data(), 'subject') === 'Réinitialisation de votre mot de passe appjeunesse-kzi'
                && str_contains(data_get($request->data(), 'htmlContent'), '/reinitialiser-mot-de-passe/');
        });
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
