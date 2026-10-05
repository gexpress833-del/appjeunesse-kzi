<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EcodimClass;
use App\Models\EcodimClassMember;
use App\Models\Event;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcodimAttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ecodim_class_attendance_records_only_active_class_members_and_notifies_relevant_users(): void
    {
        $ecodimDepartment = $this->ecodimDepartment();
        $globalResponsible = $this->userWithEcodimRole('responsable_ecodim', $ecodimDepartment->id);
        $ecodimObserver = $this->userWithEcodimRole('animateur_ecodim', $ecodimDepartment->id);

        $classResponsibleMember = Member::factory()->create(['name' => 'Responsable Classe A']);
        $classResponsible = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $classResponsibleMember->id,
        ]);
        $otherClassResponsibleMember = Member::factory()->create(['name' => 'Responsable Classe B']);
        $otherClassResponsible = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $otherClassResponsibleMember->id,
        ]);
        $this->addEcodimMembership($classResponsible, $ecodimDepartment);
        $this->addEcodimMembership($otherClassResponsible, $ecodimDepartment);

        $class = EcodimClass::create([
            'name' => 'Classe des Aigles',
            'level' => 'intermediate',
            'status' => 'active',
            'responsible_member_id' => $classResponsibleMember->id,
        ]);
        $otherClass = EcodimClass::create([
            'name' => 'Classe des Lys',
            'level' => 'beginner',
            'status' => 'active',
            'responsible_member_id' => $otherClassResponsibleMember->id,
        ]);

        $activeMember = $this->createEcodimMember('Enfant actif', $ecodimDepartment);
        $inactiveMember = $this->createEcodimMember('Enfant désinscrit', $ecodimDepartment);
        $expiredMember = $this->createEcodimMember('Affectation expirée', $ecodimDepartment);
        $otherClassMember = $this->createEcodimMember('Enfant autre classe', $ecodimDepartment);
        $memberAccount = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $activeMember->id,
        ]);
        $this->addEcodimMembership($memberAccount, $ecodimDepartment);

        EcodimClassMember::create(['class_id' => $class->id, 'member_id' => $activeMember->id, 'status' => 'active']);
        EcodimClassMember::create(['class_id' => $class->id, 'member_id' => $inactiveMember->id, 'status' => 'inactive']);
        EcodimClassMember::create([
            'class_id' => $class->id,
            'member_id' => $expiredMember->id,
            'status' => 'active',
            'ends_at' => now()->subDay(),
        ]);
        EcodimClassMember::create(['class_id' => $otherClass->id, 'member_id' => $otherClassMember->id, 'status' => 'active']);

        $event = Event::create([
            'name' => 'Leçon dominicale des Aigles',
            'date' => now()->addDay(),
            'created_by' => $globalResponsible->username,
            'dept' => $class->name,
            'portal' => 'ecodim',
        ]);

        $this->actingAs($globalResponsible)
            ->get(route('ecodim.attendances.pick'))
            ->assertOk()
            ->assertSee($event->name)
            ->assertSee(route('ecodim.attendances.sheet', ['event' => $event, 'dept' => $class->name]));

        $this->actingAs($globalResponsible)
            ->get(route('ecodim.attendances.sheet', ['event' => $event, 'dept' => $class->name]))
            ->assertOk()
            ->assertSee($activeMember->name)
            ->assertDontSee($inactiveMember->name)
            ->assertDontSee($expiredMember->name)
            ->assertDontSee($otherClassMember->name)
            ->assertSee('Enregistrer les présences');

        $this->actingAs($globalResponsible)
            ->post(route('ecodim.attendances.store', $event), [
                'dept' => $class->name,
                'statuses' => [
                    $activeMember->id => 'present',
                    $inactiveMember->id => 'absent',
                    $expiredMember->id => 'late',
                    $otherClassMember->id => 'present',
                ],
            ])
            ->assertRedirect(route('ecodim.attendances.sheet', ['event' => $event, 'dept' => $class->name]));

        $this->assertDatabaseHas('attendances', [
            'event_id' => $event->id,
            'member_id' => $activeMember->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'member_id' => $inactiveMember->id]);
        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'member_id' => $expiredMember->id]);
        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'member_id' => $otherClassMember->id]);

        $memberNotification = $memberAccount->notifications()->get()->first(fn ($notification): bool => data_get($notification->data, 'type') === 'attendance_recorded');
        $this->assertNotNull($memberNotification);
        $this->assertSame('ecodim', data_get($memberNotification->data, 'portal'));
        $this->assertSame('present', data_get($memberNotification->data, 'status'));

        $this->assertTrue($classResponsible->notifications()->get()->contains(fn ($notification): bool => data_get($notification->data, 'type') === 'attendance_batch_recorded'
            && data_get($notification->data, 'portal') === 'ecodim'
            && data_get($notification->data, 'event_id') === $event->id));
        $this->assertTrue($ecodimObserver->notifications()->get()->contains(fn ($notification): bool => data_get($notification->data, 'type') === 'attendance_batch_recorded'
            && data_get($notification->data, 'portal') === 'ecodim'));
        $this->assertFalse($otherClassResponsible->notifications()->get()->contains(fn ($notification): bool => data_get($notification->data, 'type') === 'attendance_batch_recorded'));
        $this->assertFalse($globalResponsible->notifications()->get()->contains(fn ($notification): bool => data_get($notification->data, 'type') === 'attendance_batch_recorded'));
    }

    public function test_ecodim_class_responsible_cannot_record_attendance_for_another_class(): void
    {
        $ecodimDepartment = $this->ecodimDepartment();
        $responsibleMember = Member::factory()->create(['name' => 'Responsable classe assignée']);
        $responsible = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $responsibleMember->id,
        ]);
        $this->addEcodimMembership($responsible, $ecodimDepartment);
        $class = EcodimClass::create([
            'name' => 'Classe assignée',
            'status' => 'active',
            'responsible_member_id' => $responsibleMember->id,
        ]);
        $otherClass = EcodimClass::create(['name' => 'Classe non assignée', 'status' => 'active']);
        $member = $this->createEcodimMember('Enfant hors classe', $ecodimDepartment);
        EcodimClassMember::create(['class_id' => $otherClass->id, 'member_id' => $member->id, 'status' => 'active']);
        $event = Event::create([
            'name' => 'Leçon classe non assignée',
            'date' => now()->addDay(),
            'created_by' => $responsible->username,
            'dept' => $otherClass->name,
            'portal' => 'ecodim',
        ]);

        $this->actingAs($responsible)
            ->post(route('ecodim.attendances.store', $event), [
                'dept' => $otherClass->name,
                'statuses' => [$member->id => 'present'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'member_id' => $member->id]);
    }

    public function test_ecodim_responsible_can_create_an_event_for_an_assigned_class(): void
    {
        $ecodimDepartment = $this->ecodimDepartment();
        $responsible = $this->userWithEcodimRole('responsable_ecodim', $ecodimDepartment->id);
        $class = EcodimClass::create(['name' => 'Classe des Oliviers', 'status' => 'active']);

        $this->actingAs($responsible)
            ->get(route('ecodim.events.create'))
            ->assertOk()
            ->assertSee($class->name);

        $this->actingAs($responsible)
            ->post(route('ecodim.events.store'), [
                'name' => 'Leçon ECODIM',
                'date' => now()->addWeek()->format('Y-m-d H:i:s'),
                'dept' => $class->name,
            ])
            ->assertRedirect(route('ecodim.events.index'));

        $this->assertDatabaseHas('events', [
            'name' => 'Leçon ecodim',
            'portal' => 'ecodim',
            'dept' => $class->name,
            'created_by' => $responsible->username,
        ]);

        $this->actingAs($responsible)
            ->get(route('ecodim.events.index'))
            ->assertOk()
            ->assertSee('Leçon ecodim')
            ->assertSee(route('ecodim.attendances.sheet', [
                'event' => Event::query()->where('portal', 'ecodim')->where('name', 'Leçon ecodim')->firstOrFail(),
                'dept' => $class->name,
            ]));
    }

    private function ecodimDepartment(): Department
    {
        return Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
    }

    private function userWithEcodimRole(string $roleSlug, int $scopeId): User
    {
        $normalizedSlug = match ($roleSlug) {
            'responsable_ecodim', 'animateur_ecodim', 'enseignant_ecodim', 'leader_ecodim' => 'ecodim_manager',
            default => $roleSlug,
        };

        $member = Member::factory()->create(['name' => str($normalizedSlug)->replace('_', ' ')->title()]);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $member->id,
        ]);
        $role = Role::query()->firstOrCreate(['slug' => $normalizedSlug], ['name' => str($normalizedSlug)->replace('_', ' ')->title(), 'status' => 'active']);

        Permission::query()->whereIn('slug', [
            'ecodim.classes.manage',
            'ecodim.attendance.manage',
            'ecodim.events.manage',
            'ecodim.members.view',
        ])->get()->each(fn (Permission $permission) => RolePermission::query()->firstOrCreate([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]));

        Membership::create([
            'member_id' => $member->id,
            'type' => 'ecodim',
            'entity_id' => $scopeId,
            'status' => 'active',
        ]);

        MemberRoleAssignment::create([
            'member_id' => $member->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'ecodim',
            'scope_id' => $scopeId,
            'status' => 'active',
        ]);

        return $user;
    }

    private function addEcodimMembership(User $user, Department $department): void
    {
        $memberId = $user->member_id;

        if (blank($memberId)) {
            $member = Member::factory()->create([
                'name' => $user->full_name ?: 'Membre ECODIM',
                'dept' => $department->name,
                'role' => 'Enfant',
            ]);
            $user->update(['member_id' => $member->id]);
            $memberId = $member->id;
        }

        Membership::create([
            'member_id' => $memberId,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'active',
        ]);
    }

    private function createEcodimMember(string $name, Department $department): Member
    {
        return Member::create([
            'name' => $name,
            'dept' => $department->name,
            'role' => 'Enfant',
        ]);
    }
}
