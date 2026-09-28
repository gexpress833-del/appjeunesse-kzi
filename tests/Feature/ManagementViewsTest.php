<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManagementViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_open_public_sections(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'dept' => 'Médias/DCC',
        ]);

        $this->actingAs($user)
            ->get('/galerie')
            ->assertOk();
    }

    public function test_admin_can_open_management_views(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/utilisateurs')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/carrousel')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/rapports')
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('attendances.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('attendances.report'))
            ->assertOk()
            ->assertSee('Historique par événement');
    }

    public function test_admin_can_assign_a_user_role_and_department(): void
    {
        Department::create(['name' => 'Social']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.role', $user), [
                'role' => 'responsable',
                'dept' => 'Social',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'responsable',
            'dept' => 'Social',
        ]);
    }

    public function test_member_creation_does_not_require_a_user_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('members.store'), [
                'name' => 'Membre sans compte',
                'sex' => 'male',
                'dept' => '__none__',
                'email' => 'missing.account@example.com',
            ])
            ->assertRedirect(route('members.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('members', ['email' => 'missing.account@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'missing.account@example.com']);
    }

    public function test_directory_includes_members_without_user_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $linkedMember = Member::create([
            'name' => 'Membre visible',
            'sex' => 'female',
            'email' => 'linked.member@example.com',
            'dept' => null,
            'role' => 'Fidèle',
        ]);
        $linkedUser = User::factory()->create([
            'email' => 'linked.member@example.com',
            'role' => 'user',
            'status' => 'active',
            'member_id' => $linkedMember->id,
        ]);
        Member::create([
            'name' => 'Membre orphelin',
            'sex' => 'male',
            'email' => 'orphan.member@example.com',
            'dept' => null,
            'role' => 'Fidèle',
        ]);

        $this->actingAs($admin)
            ->get(route('members.index'))
            ->assertOk()
            ->assertSee('Membre visible')
            ->assertSee('Membre orphelin');
    }

    public function test_only_admin_can_export_an_individual_member_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);
        $memberUser = User::factory()->create([
            'email' => 'pdf.member@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);
        $member = Member::create([
            'name' => 'Membre PDF',
            'sex' => 'male',
            'email' => $memberUser->email,
            'dept' => null,
            'role' => 'Fidèle',
        ]);

        $this->actingAs($admin)
            ->get(route('members.pdf', $member))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($secretariat)
            ->get(route('members.pdf', $member))
            ->assertForbidden();
    }

    public function test_admin_can_choose_a_department_but_cannot_record_attendance(): void
    {
        Department::create(['name' => 'Social']);
        Department::create(['name' => 'Médias/DCC']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Culte du dimanche',
            'date' => now()->addDay(),
            'created_by' => 'admin',
        ]);
        $member = Member::create([
            'name' => 'Membre social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.sheet', $event))
            ->assertSee('Choisir le département à suivre')
            ->assertSee('Social')
            ->assertSee('Médias/DCC')
            ->assertSee(route('attendances.sheet', ['event' => $event, 'dept' => 'Social']));

        $this->actingAs($admin)
            ->get(route('attendances.sheet', ['event' => $event, 'dept' => 'Social']))
            ->assertSee('Membre social')
            ->assertSee('Vue en lecture seule')
            ->assertDontSee('Enregistrer les présences');

        $this->actingAs($admin)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertForbidden();
    }

    public function test_standard_user_cannot_record_attendance(): void
    {
        Department::create(['name' => 'Social']);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $event = Event::create([
            'name' => 'Culte général',
            'date' => now()->addDay(),
            'created_by' => 'admin',
        ]);

        $this->actingAs($user)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_global_and_selected_department_attendance_statistics(): void
    {
        Department::create(['name' => 'Social']);
        Department::create(['name' => 'Médias/DCC']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Réunion générale',
            'date' => now()->subDay(),
            'created_by' => 'admin',
            'portal' => 'church',
        ]);
        $youthEvent = Event::create([
            'name' => 'Rencontre jeunesse Social',
            'date' => now()->subDay(),
            'created_by' => 'jeunesse',
            'portal' => 'youth',
        ]);
        $socialMember = Member::create([
            'name' => 'Membre social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);
        $youthMember = Member::create([
            'name' => 'Membre jeunesse Social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);
        $mediaMember = Member::create([
            'name' => 'Membre médias',
            'dept' => 'Médias/DCC',
            'role' => 'Membre',
        ]);
        Attendance::create([
            'member_id' => $socialMember->id,
            'event_id' => $event->id,
            'status' => 'present',
        ]);
        Attendance::create([
            'member_id' => $mediaMember->id,
            'event_id' => $event->id,
            'status' => 'absent',
        ]);
        Attendance::create([
            'member_id' => $youthMember->id,
            'event_id' => $youthEvent->id,
            'status' => 'present',
        ]);

        $this->actingAs($admin)
            ->get(route('attendances.report'))
            ->assertSee('Statistiques globales')
            ->assertSee('Membre social')
            ->assertSee('Membre médias')
            ->assertDontSee($youthMember->name)
            ->assertViewHas('summary', fn ($summary) => (int) $summary->firstWhere('dept', 'Social')->total === 1);

        $this->actingAs($admin)
            ->get(route('attendances.report', ['dept' => 'Social']))
            ->assertSee('Département filtré : Social')
            ->assertSee('Membre social')
            ->assertDontSee('Membre médias')
            ->assertDontSee($youthMember->name);
    }

    public function test_pastor_sees_department_attendance_statistics_without_recording_attendance(): void
    {
        Department::create(['name' => 'Social']);
        Department::create(['name' => 'Médias/DCC']);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Culte de dimanche',
            'date' => now()->subDay(),
            'created_by' => 'admin',
        ]);
        $socialMember = Member::create([
            'name' => 'Membre social confidentiel',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);
        $mediaMember = Member::create([
            'name' => 'Membre média confidentiel',
            'dept' => 'Médias/DCC',
            'role' => 'Membre',
        ]);
        Attendance::create([
            'member_id' => $socialMember->id,
            'event_id' => $event->id,
            'status' => 'present',
        ]);
        Attendance::create([
            'member_id' => $mediaMember->id,
            'event_id' => $event->id,
            'status' => 'late',
        ]);

        $this->actingAs($pastor)
            ->get(route('attendances.pick'))
            ->assertOk()
            ->assertViewIs('attendances.pastor')
            ->assertViewHas('departmentStats', fn ($stats) => (int) $stats->firstWhere('department', 'Social')->present === 1
                && (int) $stats->firstWhere('department', 'Médias/DCC')->late === 1)
            ->assertSee('Statistiques des présences par département')
            ->assertSee('Social')
            ->assertSee('Médias/DCC')
            ->assertSee('Présents')
            ->assertSee('En retard')
            ->assertSee('Tous les événements')
            ->assertSee('Toutes les dates')
            ->assertDontSee('Membre social confidentiel')
            ->assertDontSee('Membre média confidentiel')
            ->assertDontSee('Enregistrer les présences');

        $this->actingAs($pastor)
            ->get(route('attendances.sheet', ['event' => $event, 'dept' => 'Social']))
            ->assertOk()
            ->assertSee('Vue en lecture seule')
            ->assertDontSee('Enregistrer les présences')
            ->assertDontSee('Tout marquer présent')
            ->assertDontSee('name="statuses[', false);

        $this->actingAs($pastor)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$socialMember->id => 'absent'],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', [
            'member_id' => $socialMember->id,
            'event_id' => $event->id,
            'status' => 'present',
        ]);
    }

    public function test_pastor_can_filter_attendance_statistics_by_event_and_date_range(): void
    {
        Department::create(['name' => 'Social']);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
        ]);
        $olderEvent = Event::create([
            'name' => 'Culte du 20 août',
            'date' => '2026-08-20 09:00:00',
            'created_by' => 'admin',
        ]);
        $selectedEvent = Event::create([
            'name' => 'Culte du 26 septembre',
            'date' => '2026-09-26 09:00:00',
            'created_by' => 'admin',
        ]);
        $member = Member::create([
            'name' => 'Membre social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);
        Attendance::create([
            'member_id' => $member->id,
            'event_id' => $olderEvent->id,
            'status' => 'present',
        ]);
        Attendance::create([
            'member_id' => $member->id,
            'event_id' => $selectedEvent->id,
            'status' => 'absent',
        ]);

        $this->actingAs($pastor)
            ->get(route('attendances.pick', ['from' => '2026-09-26', 'to' => '2026-09-26']))
            ->assertSee('2026-09-26 au 2026-09-26')
            ->assertViewHas('departmentStats', fn ($stats) => (int) $stats->firstWhere('department', 'Social')->present === 0
                && (int) $stats->firstWhere('department', 'Social')->absent === 1
                && (int) $stats->firstWhere('department', 'Social')->total === 1);

        $this->actingAs($pastor)
            ->get(route('attendances.pick', ['event_id' => $olderEvent->id]))
            ->assertSee('Culte du 20 août')
            ->assertSee('20/08/2026 09:00')
            ->assertViewHas('selectedEvent', fn ($event) => $event->is($olderEvent))
            ->assertViewHas('departmentStats', fn ($stats) => (int) $stats->firstWhere('department', 'Social')->present === 1
                && (int) $stats->firstWhere('department', 'Social')->absent === 0
                && (int) $stats->firstWhere('department', 'Social')->total === 1);
    }

    public function test_responsable_can_create_department_event_and_receives_member_notification(): void
    {
        Department::create(['name' => 'Social']);

        $responsable = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        User::factory()->create([
            'email' => 'social.member@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($responsable)
            ->post(route('events.store'), [
                'name' => 'Veillée de département',
                'date' => now()->addDay()->format('Y-m-d\TH:i'),
                'description' => 'Rencontre de département',
            ])
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'name' => 'Veillée de département',
            'dept' => 'Social',
            'created_by' => $responsable->username,
        ]);

        $this->actingAs($admin)
            ->post(route('members.store'), [
                'name' => 'Nouveau membre social',
                'sex' => 'female',
                'dept' => 'Social',
                'role' => 'Membre',
                'email' => 'social.member@example.com',
                'phone' => '0909090909',
            ])
            ->assertRedirect(route('members.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $responsable->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertTrue($responsable->fresh()->unreadNotifications->isNotEmpty());
    }

    public function test_responsable_can_record_department_attendance_for_global_event(): void
    {
        Department::create(['name' => 'Social']);

        $responsable = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);
        $event = Event::create([
            'name' => 'Événement général',
            'date' => now()->addDay(),
            'created_by' => 'admin',
        ]);
        $member = Member::create([
            'name' => 'Membre social',
            'dept' => 'Social',
            'role' => 'Membre',
            'profile_photo_url' => 'https://example.com/member-photo.jpg',
        ]);
        $unassignedMember = Member::create([
            'name' => 'Membre sans département',
            'dept' => null,
            'role' => 'Fidèle',
        ]);

        $this->actingAs($responsable)
            ->get(route('members.create'))
            ->assertForbidden();

        $this->actingAs($responsable)
            ->post(route('members.store'), [
                'name' => 'Tentative responsable',
                'dept' => 'Social',
            ])
            ->assertForbidden();

        $this->actingAs($responsable)
            ->get(route('attendances.sheet', $event))
            ->assertOk()
            ->assertSee('Tout marquer présent')
            ->assertSee('https://example.com/member-photo.jpg');

        $this->actingAs($responsable)
            ->get(route('attendances.pick'))
            ->assertOk()
            ->assertSee(route('attendances.pdf', ['event_id' => $event->id]));

        $this->actingAs($responsable)
            ->get(route('attendances.sheet', ['event' => $event, 'dept' => '__none__']))
            ->assertForbidden();

        $this->actingAs($responsable)
            ->post(route('attendances.store', $event), [
                'dept' => '__none__',
                'statuses' => [$unassignedMember->id => 'present'],
            ])
            ->assertForbidden();

        $this->actingAs($responsable)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertRedirect(route('attendances.sheet', ['event' => $event, 'dept' => 'Social']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'present',
        ]);
    }

    public function test_church_department_responsable_cannot_record_youth_attendance_without_youth_assignment(): void
    {
        Department::create(['name' => 'Social']);

        $responsable = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);
        $event = Event::create([
            'name' => 'Rencontre jeunesse Social',
            'date' => now()->addDay(),
            'created_by' => 'admin',
            'dept' => 'Social',
            'portal' => 'youth',
        ]);
        $member = Member::create([
            'name' => 'Membre jeunesse Social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);

        $this->actingAs($responsable)
            ->post(route('youth.attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'event_id' => $event->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_youth_attendance_requires_a_scoped_role_for_the_target_department(): void
    {
        $socialDepartment = Department::create(['name' => 'Social']);
        Department::create(['name' => 'Chorale']);

        $youthLeader = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'dept' => 'Social',
        ]);
        $role = Role::create([
            'name' => 'Responsable jeunesse',
            'slug' => 'responsable_jeunesse',
            'status' => 'active',
        ]);
        MemberRoleAssignment::create([
            'user_id' => $youthLeader->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => $socialDepartment->id,
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Rencontre jeunesse Social',
            'date' => now()->addDay(),
            'created_by' => 'jeunesse',
            'portal' => 'youth',
        ]);
        $socialMember = Member::create([
            'name' => 'Membre jeunesse Social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);
        $choraleMember = Member::create([
            'name' => 'Membre jeunesse Chorale',
            'dept' => 'Chorale',
            'role' => 'Membre',
        ]);

        $this->actingAs($youthLeader)
            ->post(route('youth.attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$socialMember->id => 'present'],
            ])
            ->assertRedirect(route('youth.attendances.sheet', ['event' => $event, 'dept' => 'Social']));

        $this->assertDatabaseHas('attendances', [
            'event_id' => $event->id,
            'member_id' => $socialMember->id,
            'status' => 'present',
        ]);

        $this->actingAs($youthLeader)
            ->get(route('youth.attendances.pick'))
            ->assertOk()
            ->assertSee('Rencontre jeunesse Social')
            ->assertSee(route('youth.attendances.sheet', ['event' => $event, 'dept' => 'Social']))
            ->assertSee(route('youth.attendances.pdf', ['event_id' => $event->id, 'dept' => 'Social']));

        $this->actingAs($youthLeader)
            ->get(route('youth.attendances.sheet', ['event' => $event, 'dept' => 'Social']))
            ->assertOk()
            ->assertSee('Membre jeunesse Social')
            ->assertDontSee('Membre jeunesse Chorale')
            ->assertSee('Enregistrer les présences');

        $this->actingAs($youthLeader)
            ->post(route('youth.attendances.store', $event), [
                'dept' => 'Chorale',
                'statuses' => [$choraleMember->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'event_id' => $event->id,
            'member_id' => $choraleMember->id,
        ]);

        $churchEvent = Event::create([
            'name' => 'Réunion du département Social',
            'date' => now()->addDay(),
            'created_by' => 'pasteur',
            'dept' => 'Social',
            'portal' => 'church',
        ]);

        $this->actingAs($youthLeader)
            ->post(route('youth.attendances.store', $churchEvent), [
                'dept' => 'Social',
                'statuses' => [$socialMember->id => 'present'],
            ])
            ->assertForbidden();

        $this->actingAs($youthLeader)
            ->post(route('attendances.store', $event), [
                'dept' => 'Social',
                'statuses' => [$socialMember->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'event_id' => $churchEvent->id,
            'member_id' => $socialMember->id,
        ]);
    }

    public function test_youth_event_creation_and_listing_use_portal_and_assigned_department(): void
    {
        $department = Department::create(['name' => 'Social']);
        Department::create(['name' => 'Chorale']);
        $youthLeader = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);
        $role = Role::create([
            'name' => 'Responsable jeunesse',
            'slug' => 'responsable_jeunesse',
            'status' => 'active',
        ]);
        MemberRoleAssignment::create([
            'user_id' => $youthLeader->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => $department->id,
            'status' => 'active',
        ]);
        $churchEvent = Event::create([
            'name' => 'Culte de l’Église',
            'date' => now()->addDay(),
            'created_by' => 'pasteur',
            'dept' => 'Social',
            'portal' => 'church',
        ]);
        Notification::fake();

        $this->actingAs($youthLeader)
            ->post(route('youth.events.store'), [
                'name' => 'Rencontre jeunesse',
                'date' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'description' => 'Rencontre du département jeunesse.',
                'dept' => 'Chorale',
                'portal' => 'church',
            ])
            ->assertRedirect(route('youth.events.index'));

        $this->assertDatabaseHas('events', [
            'name' => 'Rencontre jeunesse',
            'dept' => 'Social',
            'portal' => 'youth',
        ]);

        $this->actingAs($youthLeader)
            ->get(route('youth.events.index'))
            ->assertSee('Rencontre jeunesse')
            ->assertDontSee($churchEvent->name);

        $this->actingAs($youthLeader)
            ->get(route('events.index'))
            ->assertSee($churchEvent->name)
            ->assertDontSee('Rencontre jeunesse');
    }

    public function test_secretariat_can_only_view_attendance_and_cannot_record_it(): void
    {
        $secretariat = User::factory()->create([
            'role' => 'secretariat',
            'status' => 'active',
        ]);
        $event = Event::create([
            'name' => 'Culte général',
            'date' => now()->addDay(),
            'created_by' => 'secretariat',
        ]);
        $member = Member::create([
            'name' => 'Fidèle sans fonction',
            'dept' => null,
            'role' => 'Fidèle',
        ]);

        $this->actingAs($secretariat)
            ->get(route('attendances.sheet', ['event' => $event, 'dept' => '__none__']))
            ->assertOk()
            ->assertSee('Vue en lecture seule')
            ->assertDontSee('Enregistrer les présences');

        $this->actingAs($secretariat)
            ->post(route('attendances.store', $event), [
                'dept' => '__none__',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'event_id' => $event->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_responsable_cannot_open_global_report_screen_but_can_export_department_report(): void
    {
        Department::create(['name' => 'Social']);
        Department::create(['name' => 'Médias/DCC']);

        $responsable = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);

        $member = Member::create([
            'name' => 'Membre social',
            'dept' => 'Social',
            'role' => 'Membre',
        ]);

        $socialEvent = Event::create([
            'name' => 'Événement social',
            'date' => now()->addDay(),
            'dept' => 'Social',
            'created_by' => 'responsable',
        ]);

        $globalEvent = Event::create([
            'name' => 'Événement global',
            'date' => now()->addDay(),
            'created_by' => 'admin',
        ]);

        $otherEvent = Event::create([
            'name' => 'Événement médias',
            'date' => now()->addDay(),
            'dept' => 'Médias/DCC',
            'created_by' => 'admin',
        ]);

        Attendance::create([
            'member_id' => $member->id,
            'event_id' => $socialEvent->id,
            'status' => 'present',
        ]);

        Attendance::create([
            'member_id' => $member->id,
            'event_id' => $globalEvent->id,
            'status' => 'late',
        ]);

        $this->actingAs($responsable)
            ->get(route('attendances.report'))
            ->assertForbidden();

        $this->actingAs($responsable)
            ->get(route('attendances.report', ['event_id' => $otherEvent->id]))
            ->assertForbidden();

        $this->actingAs($responsable)
            ->get(route('attendances.pdf', ['event_id' => $globalEvent->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
