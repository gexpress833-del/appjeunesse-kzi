<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertOk();

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

    public function test_secretariat_can_record_attendance_for_members_without_department(): void
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

        $this->actingAs($secretariat)
            ->post(route('members.store'), [
                'name' => 'Fidèle sans fonction',
                'dept' => '__none__',
                'role' => null,
            ])
            ->assertRedirect(route('members.index'));

        $member = Member::where('name', 'Fidèle sans fonction')->firstOrFail();

        $this->assertNull($member->dept);

        $this->actingAs($secretariat)
            ->get(route('attendances.sheet', ['event' => $event, 'dept' => '__none__']))
            ->assertOk();

        $this->actingAs($secretariat)
            ->post(route('attendances.store', $event), [
                'dept' => '__none__',
                'statuses' => [$member->id => 'present'],
            ])
            ->assertRedirect(route('attendances.sheet', ['event' => $event, 'dept' => '__none__']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'event_id' => $event->id,
            'member_id' => $member->id,
            'status' => 'present',
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
