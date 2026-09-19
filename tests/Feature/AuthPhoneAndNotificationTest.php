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

    public function test_registration_requires_sex(): void
    {
        $response = $this->from('/register')->post('/register', [
            'username' => 'jeandupont',
            'full_name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '0812345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['sex']);
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

    public function test_user_can_manage_own_notifications(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $notification = $user->notify(new \App\Notifications\AccountValidated);

        $notification = $user->notifications()->latest()->first();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($user)
            ->delete(route('notifications.destroy', $notification))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_user_can_delete_multiple_notifications_at_once(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $first = $user->notify(new \App\Notifications\AccountValidated);
        $second = $user->notify(new \App\Notifications\RoleUpdated($user, 'user', 'Médias'));

        $notifications = $user->notifications()->latest()->take(2)->get();

        $this->actingAs($user)
            ->post(route('notifications.bulk.destroy'), [
                'notifications' => $notifications->pluck('id')->all(),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_video_views_are_counted_once_per_real_user(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $live = \App\Models\HomeContent::create([
            'type' => 'live_stream',
            'title' => 'Culte en direct',
            'content' => 'Live de démonstration',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'live',
            'is_active' => true,
        ]);

        $archive = \App\Models\VideoArchive::firstOrCreate(
            ['media_url' => $live->media_url],
            ['title' => $live->title, 'broadcast_type' => $live->broadcast_type]
        );

        $this->actingAs($user)->withSession([])->get('/');
        $this->assertSame(1, $archive->fresh()->views_count);

        $this->actingAs($user)->withSession([])->get('/');
        $this->assertSame(1, $archive->fresh()->views_count);
    }

    public function test_public_video_archive_page_lists_all_videos_by_type(): void
    {
        $publisher = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        \App\Models\VideoArchive::create([
            'title' => 'Culte du dimanche',
            'description' => 'Direct réservé aux fidèles.',
            'media_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'broadcast_type' => 'live',
            'views_count' => 120,
            'published_by' => $publisher->id,
        ]);

        \App\Models\VideoArchive::create([
            'title' => 'Retransmission jeunesse',
            'description' => 'Retrouvez la retransmission complète.',
            'media_url' => 'https://www.facebook.com/watch/?v=123456789',
            'broadcast_type' => 'replay',
            'views_count' => 84,
            'published_by' => $publisher->id,
        ]);

        $response = $this->get(route('videos.archive'));

        $response->assertOk();
        $response->assertSee('Archives vidéo');
        $response->assertSee('Culte du dimanche');
        $response->assertSee('Retransmission jeunesse');
        $response->assertSee('En direct');
        $response->assertSee('Retransmission');
    }
}
