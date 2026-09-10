<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthPhoneAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '+243812345678',
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => '0812345678',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_phone_number(): void
    {
        $response = $this->from('/register')->post('/register', [
            'username' => 'jeandupont',
            'full_name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_validating_account_creates_in_app_notification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.validate', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_account_validation_sends_email_to_registration_address(): void
    {
        Http::fake();
        config(['services.brevo.api_key' => 'test-key']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($admin)->patch(route('users.validate', $user));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && data_get($request->data(), 'to.0.email') === 'member@example.com');
    }

    public function test_active_regular_users_receive_event_and_broadcast_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $regularUser = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $pendingUser = User::factory()->create(['role' => 'user', 'status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('events.store'), [
                'name' => 'Événement public',
                'date' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $regularUser->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $pendingUser->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($admin)
            ->post(route('live.save'), [
                'title' => 'Culte en direct',
                'media_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
                'broadcast_type' => 'live',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $regularUser->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseHas('home_contents', [
            'type' => 'live_stream',
            'broadcast_type' => 'live',
        ]);
    }

    public function test_social_follow_up_assignment_notifies_assignee(): void
    {
        Department::create(['name' => 'Social']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $assignee = User::factory()->create(['role' => 'responsable', 'dept' => 'Social', 'status' => 'active']);
        $member = Member::create(['name' => 'Membre social', 'dept' => 'Social', 'role' => 'Membre']);

        $this->actingAs($admin)
            ->post(route('social-visits.store'), [
                'member_id' => $member->id,
                'assigned_to' => $assignee->id,
                'visit_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'reason' => 'Accompagnement',
                'status' => 'planned',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $assignee->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_responsable_uses_his_department_without_choosing_a_department(): void
    {
        $responsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Médias',
            'status' => 'active',
        ]);

        $response = $this->actingAs($responsable)->get('/presences');

        $response->assertOk();
        $response->assertDontSee('Sélection du département');
        $response->assertSee('Médias');
    }

    public function test_attendance_recording_notifies_managers_once_without_notifying_recorder(): void
    {
        Department::create(['name' => 'Social']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $responsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Social',
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Culte de test',
            'date' => now()->addDay(),
            'created_by' => $admin->username,
        ]);
        $member = Member::create([
            'name' => 'Membre de test',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);

        $this->actingAs($responsable)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $responsable->id,
            'notifiable_type' => User::class,
        ]);
    }
}
