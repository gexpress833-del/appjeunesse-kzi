<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Department;
use App\Models\Event;
use App\Models\HomeContent;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Role;
use App\Models\User;
use App\Models\VideoArchive;
use App\Notifications\AccountValidated;
use App\Notifications\RoleUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthPhoneAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_opening_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response
            ->assertRedirect(route('login'));
        $response->assertSessionHas('url.intended', route('notifications.index'));
    }

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

        $response->assertRedirect(route('dashboard.youth'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_youth_responsible_is_redirected_to_youth_dashboard_from_login_page(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'youth'], ['name' => 'Portail jeunesse']);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);
        $role = Role::query()->firstOrCreate(
            ['slug' => 'responsable_jeunesse'],
            ['name' => 'Responsable jeunesse', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard.youth'));

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard.youth'));
    }

    public function test_existing_ecodim_responsible_is_redirected_to_ecodim_dashboard_from_login_page(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);
        $role = Role::query()->firstOrCreate(
            ['slug' => 'responsable_ecodim'],
            ['name' => 'Responsable ECODIM', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'ecodim',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard.ecodim'));

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard.ecodim'));
    }

    public function test_existing_ecodim_responsible_login_clears_stale_youth_portal_session(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'password' => bcrypt('password123'),
        ]);
        $role = Role::query()->firstOrCreate(
            ['slug' => 'responsable_ecodim'],
            ['name' => 'Responsable ECODIM', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'ecodim',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);

        $this->withSession(['active_portal' => 'youth'])
            ->post(route('login.attempt'), [
                'login' => $user->username,
                'password' => 'password123',
            ])
            ->assertRedirect(route('dashboard.ecodim'))
            ->assertSessionHas('active_portal', 'ecodim');
    }

    public function test_validated_ecodim_responsible_login_redirects_to_ecodim_dashboard(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Http::fake();
        config(['services.brevo.api_key' => 'test-key']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'ecodimresponsable',
                'full_name' => 'Responsable ECODIM',
                'email' => 'ecodim.responsable@example.com',
                'phone' => '+243812345680',
                'password' => 'password123',
                'role' => 'responsable',
                'dept' => $department->name,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'ecodimresponsable')->firstOrFail();
        $this->assertSame('pending', $user->status);
        $this->assertDatabaseHas('member_role_assignments', [
            'user_id' => $user->id,
            'scope_type' => 'ecodim',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->patch(route('users.validate', $user))->assertRedirect();
        $this->post(route('logout'))->assertRedirect(route('home'));

        $this->post(route('login.attempt'), [
            'login' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard.ecodim'));

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

    public function test_admin_account_creation_notifies_admin_and_secretariat(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'newmember',
                'full_name' => 'Nouveau membre',
                'email' => 'newmember@example.com',
                'phone' => '+243812345678',
                'password' => 'password123',
                'role' => 'user',
            ])
            ->assertRedirect(route('users.index'));

        $createdUser = User::query()->where('username', 'newmember')->firstOrFail();
        $this->assertSame('pending', $createdUser->status);

        foreach ([$admin, $secretariat] as $recipient) {
            $this->assertSame(1, $recipient->notifications()->where('data->account_id', $createdUser->id)->count());
        }

        $this->assertSame(
            '/utilisateurs?status=pending',
            data_get($admin->notifications()->where('data->account_id', $createdUser->id)->first()->data, 'click_action'),
        );
        $this->assertSame(
            '/notifications',
            data_get($secretariat->notifications()->where('data->account_id', $createdUser->id)->first()->data, 'click_action'),
        );

        $this->actingAs($secretariat)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Nouveau compte à valider')
            ->assertSee('Nouveau compte');
    }

    public function test_admin_account_creation_respects_disabled_registration_notifications(): void
    {
        AppSetting::current()->update(['notification_settings' => ['new_registration' => false]]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'quietmember',
                'full_name' => 'Membre sans alerte',
                'email' => 'quietmember@example.com',
                'phone' => '+243812345679',
                'password' => 'password123',
                'role' => 'user',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('users', ['username' => 'quietmember', 'status' => 'pending']);
    }

    public function test_admin_creating_a_youth_responsible_assigns_the_youth_portal_role(): void
    {
        $youthDepartment = Department::query()->firstOrCreate(['code' => 'youth'], ['name' => 'Portail jeunesse']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'username' => 'youthresponsable',
                'full_name' => 'Responsable jeunesse',
                'email' => 'youth.responsable@example.com',
                'phone' => '+243812345681',
                'password' => 'password123',
                'role' => 'responsable',
                'dept' => $youthDepartment->name,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('username', 'youthresponsable')->firstOrFail();
        $this->assertDatabaseMissing('memberships', [
            'type' => 'youth',
            'entity_id' => $youthDepartment->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('member_role_assignments', [
            'user_id' => $user->id,
            'scope_type' => 'youth',
            'scope_id' => $youthDepartment->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_created_regular_accounts_keep_their_selected_portal_membership(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $portalDepartments = collect([
            Department::query()->firstOrCreate(['code' => 'youth'], ['name' => 'Portail jeunesse']),
            Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']),
        ]);

        foreach ($portalDepartments as $index => $department) {
            $username = 'portaluser'.$index;

            $this->actingAs($admin)
                ->post(route('users.store'), [
                    'username' => $username,
                    'full_name' => 'Membre '.$department->name,
                    'email' => $username.'@example.com',
                    'phone' => '+24381234568'.($index + 2),
                    'password' => 'password123',
                    'role' => 'user',
                    'dept' => $department->name,
                ])
                ->assertRedirect(route('users.index'));

            $createdUser = User::query()->where('username', $username)->firstOrFail();
            $this->assertDatabaseMissing('memberships', [
                'type' => $department->code,
                'entity_id' => $department->id,
                'status' => 'active',
            ]);
            $this->assertNull($createdUser->member_id);
        }
    }

    public function test_church_administration_can_view_and_validate_pending_accounts_from_all_portals(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $pastor = User::factory()->create(['role' => 'pasteur_n1', 'status' => 'active']);
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);
        $youthUser = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'full_name' => 'Compte jeunesse en attente',
            'dept' => 'Portail jeunesse',
        ]);
        $ecodimUser = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'full_name' => 'Compte ECODIM en attente',
            'dept' => 'ECODIM',
        ]);

        foreach ([$admin, $pastor, $secretariat] as $approver) {
            $this->actingAs($approver)
                ->get(route('users.index'))
                ->assertOk()
                ->assertSee($youthUser->full_name)
                ->assertSee($ecodimUser->full_name);
        }

        $this->actingAs($secretariat)
            ->patch(route('users.validate', $youthUser))
            ->assertRedirect();
        $this->actingAs($pastor)
            ->patch(route('users.validate', $ecodimUser))
            ->assertRedirect();

        $this->assertSame('active', $youthUser->fresh()->status);
        $this->assertSame('active', $ecodimUser->fresh()->status);
    }

    public function test_youth_responsible_can_only_list_and_validate_youth_accounts(): void
    {
        $youthDepartment = Department::query()->firstOrCreate(['code' => 'youth'], ['name' => 'Portail jeunesse']);
        $ecodimDepartment = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $responsible = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $youthRole = Role::query()->firstOrCreate(
            ['slug' => 'responsable_jeunesse'],
            ['name' => 'Responsable jeunesse', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $responsible->id,
            'role_id' => $youthRole->id,
            'scope_type' => 'youth',
            'scope_id' => $youthDepartment->id,
            'status' => 'active',
        ]);
        $youthMember = Member::factory()->create([
            'name' => 'Compte jeunesse en attente',
            'dept' => $youthDepartment->name,
            'role' => 'Membre',
        ]);
        $ecodimMember = Member::factory()->create([
            'name' => 'Compte ECODIM privé',
            'dept' => $ecodimDepartment->name,
            'role' => 'Membre',
        ]);
        $youthUser = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'full_name' => 'Compte jeunesse en attente',
            'dept' => $youthDepartment->name,
            'member_id' => $youthMember->id,
        ]);
        $ecodimUser = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'full_name' => 'Compte ECODIM privé',
            'dept' => $ecodimDepartment->name,
            'member_id' => $ecodimMember->id,
        ]);
        Membership::create([
            'member_id' => $youthMember->id,
            'type' => 'youth',
            'entity_id' => $youthDepartment->id,
            'status' => 'active',
        ]);
        Membership::create([
            'member_id' => $ecodimMember->id,
            'type' => 'ecodim',
            'entity_id' => $ecodimDepartment->id,
            'status' => 'active',
        ]);

        $this->actingAs($responsible)
            ->get(route('youth.users.index'))
            ->assertOk()
            ->assertSee($youthUser->full_name)
            ->assertDontSee($ecodimUser->full_name);

        $this->actingAs($responsible)
            ->patch(route('youth.users.validate', $youthUser))
            ->assertRedirect();
        $this->actingAs($responsible)
            ->patch(route('youth.users.validate', $ecodimUser))
            ->assertForbidden();

        $this->assertSame('active', $youthUser->fresh()->status);
        $this->assertSame('pending', $ecodimUser->fresh()->status);
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
        Department::create(['name' => 'Médias']);
        $member = Member::factory()->create([
            'name' => 'Responsable médias',
            'dept' => 'Médias',
            'role' => 'Responsable',
        ]);
        $responsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Médias',
            'status' => 'active',
            'member_id' => $member->id,
        ]);
        Membership::create([
            'member_id' => $member->id,
            'type' => 'church',
            'entity_id' => 1,
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
        $member = Member::create([
            'name' => 'Responsable social',
            'dept' => 'Social',
            'role' => 'Responsable',
        ]);
        $responsable = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'Social',
            'status' => 'active',
            'member_id' => $member->id,
        ]);
        Membership::create([
            'member_id' => $member->id,
            'type' => 'church',
            'entity_id' => 1,
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Culte de test',
            'date' => now()->addDay(),
            'created_by' => $admin->username,
        ]);
        $memberAttendance = Member::create([
            'name' => 'Membre de test',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);

        $this->actingAs($responsable)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$memberAttendance->id => 'present'],
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
        $notification = $user->notify(new AccountValidated);

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

    public function test_admin_can_open_notifications_page_from_the_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Centre d’alertes')
            ->assertSee('Tout est calme');
    }

    public function test_admin_sees_only_one_attendance_link_in_the_sidebar(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $this->assertSame(1, substr_count($response->getContent(), 'href="'.route('attendances.pick').'"'));
    }

    public function test_notification_page_renders_dark_card_and_actions_for_unread_notification(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->notify(new AccountValidated);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Compte validé')
            ->assertSee('notification-card', false)
            ->assertSee('notification-read-button', false)
            ->assertSee('notification-delete-button', false);
    }

    public function test_user_can_delete_multiple_notifications_at_once(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $first = $user->notify(new AccountValidated);
        $second = $user->notify(new RoleUpdated($user, 'user', 'Médias'));

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

        $live = HomeContent::create([
            'type' => 'live_stream',
            'title' => 'Culte en direct',
            'content' => 'Live de démonstration',
            'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'broadcast_type' => 'live',
            'is_active' => true,
        ]);

        $archive = VideoArchive::firstOrCreate(
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

        VideoArchive::create([
            'title' => 'Culte du dimanche',
            'description' => 'Direct réservé aux fidèles.',
            'media_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
            'broadcast_type' => 'live',
            'views_count' => 120,
            'published_by' => $publisher->id,
        ]);

        VideoArchive::create([
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
