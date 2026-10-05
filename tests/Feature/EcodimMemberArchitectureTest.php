<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EcodimClass;
use App\Models\EcodimClassMember;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EcodimMemberArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_is_a_member_with_ecodim_membership_and_no_user_is_required(): void
    {
        $department = Department::query()->firstOrCreate(
            ['code' => 'ecodim'],
            ['name' => 'ECODIM'],
        );
        $child = Member::create([
            'name' => 'Enfant ECODIM',
            'birth_date' => '2015-10-04',
        ]);
        $class = EcodimClass::create([
            'name' => 'Classe des Oliviers',
            'status' => 'active',
        ]);

        EcodimClassMember::create([
            'class_id' => $class->id,
            'member_id' => $child->id,
            'status' => 'active',
        ]);

        $this->assertNull($child->user()->first());
        $this->assertSame($child->id, $class->members()->firstOrFail()->member_id);
        $this->assertDatabaseHas('memberships', [
            'member_id' => $child->id,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'active',
        ]);
    }

    public function test_member_age_is_calculated_from_birth_date(): void
    {
        $this->travelTo(Carbon::parse('2026-10-03 12:00:00'));
        $member = Member::create([
            'name' => 'Enfant ECODIM',
            'birth_date' => '2015-10-04',
        ]);

        $this->assertSame(10, $member->age());

        $member->update(['birth_date' => '2010-10-02']);

        $this->assertSame(16, $member->fresh()->age());
    }

    public function test_class_responsible_is_a_member_and_foreign_key_targets_members(): void
    {
        $responsible = Member::create(['name' => 'Responsable de classe']);
        $class = EcodimClass::create([
            'name' => 'Classe des Aigles',
            'responsible_member_id' => $responsible->id,
        ]);
        $foreignKey = collect(Schema::getForeignKeys('ecodim_classes'))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['responsible_member_id']);

        $this->assertInstanceOf(Member::class, $class->responsibleMember);
        $this->assertSame($responsible->id, $class->responsibleMember->id);
        $this->assertNotNull($foreignKey);
        $this->assertSame('members', $foreignKey['foreign_table']);
        $this->assertSame('set null', strtolower($foreignKey['on_delete']));
    }

    public function test_class_responsible_permission_is_limited_to_the_assigned_class_scope(): void
    {
        [$user, $responsible] = $this->activeEcodimUser('Responsable classe A');
        $classA = EcodimClass::create([
            'name' => 'Classe A',
            'responsible_member_id' => $responsible->id,
        ]);
        $classB = EcodimClass::create(['name' => 'Classe B']);
        $this->assignEcodimRole($user, 'ecodim_class_responsible', 'ecodim_class', $classA->id);

        $this->assertTrue($user->hasActiveEcodimMembership());
        $this->assertTrue($user->hasEcodimPermission('ecodim.attendance.manage'));
        $this->assertTrue($user->hasEcodimClassPermission($classA, 'ecodim.attendance.manage'));
        $this->assertNotNull(Gate::getPolicyFor(EcodimClass::class));
        $this->assertTrue($user->can('manageAttendance', $classA));
        $this->assertFalse($user->can('manageAttendance', $classB));
        $this->assertTrue($user->ecodimAttendanceClasses()->contains('id', $classA->id));
        $this->assertFalse($user->ecodimAttendanceClasses()->contains('id', $classB->id));
    }

    public function test_ecodim_manager_can_manage_all_classes_with_global_scope(): void
    {
        [$user, , $department] = $this->activeEcodimUser('Responsable global');
        $classA = EcodimClass::create(['name' => 'Classe globale A']);
        $classB = EcodimClass::create(['name' => 'Classe globale B']);
        $this->assignEcodimRole($user, 'ecodim_manager', 'ecodim', $department->id);

        $this->assertTrue($user->hasEcodimClassPermission($classA, 'ecodim.classes.manage'));
        $this->assertTrue($user->can('manage', $classA));
        $this->assertTrue($user->can('manageAttendance', $classB));
        $this->assertCount(2, $user->ecodimAttendanceClasses());
    }

    public function test_family_guardian_does_not_receive_ecodim_administration_permissions(): void
    {
        [$guardian] = $this->activeEcodimUser('Parent ECODIM');
        $child = Member::create(['name' => 'Enfant ECODIM']);
        $family = Family::create(['name' => 'Famille ECODIM']);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $guardian->member_id,
            'relationship_type' => 'guardian',
            'status' => 'active',
        ]);
        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $child->id,
            'relationship_type' => 'child',
            'status' => 'active',
        ]);

        $class = EcodimClass::create(['name' => 'Classe de son enfant']);

        $this->assertFalse($guardian->hasEcodimPermission('ecodim.classes.manage'));
        $this->assertFalse($guardian->hasEcodimClassPermission($class, 'ecodim.attendance.manage'));
        $this->assertTrue($guardian->member->familyLinks()->exists());
    }

    public function test_technical_user_without_member_has_no_ecodim_business_access(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $technicalUser = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => null,
        ]);
        $class = EcodimClass::create(['name' => 'Classe technique']);
        $this->assignEcodimRole($technicalUser, 'ecodim_manager', 'ecodim', $department->id);

        $this->assertFalse($technicalUser->canAccessPortal('ecodim'));
        $this->assertFalse($technicalUser->hasEcodimPermission('ecodim.classes.manage'));
        $this->assertFalse($technicalUser->canManageAttendance('ecodim', $class->name));
        $this->assertCount(0, $technicalUser->ecodimAttendanceClasses());
    }

    /** @return array{User, Member, Department} */
    private function activeEcodimUser(string $name): array
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $member = Member::create(['name' => $name]);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $member->id,
        ]);

        Membership::create([
            'member_id' => $member->id,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'active',
        ]);

        return [$user, $member, $department];
    }

    private function assignEcodimRole(User $user, string $slug, string $scopeType, int $scopeId): void
    {
        $role = Role::query()->where('slug', $slug)->firstOrFail();

        Permission::query()
            ->whereIn('slug', [
                'ecodim.classes.manage',
                'ecodim.attendance.manage',
                'ecodim.events.manage',
                'ecodim.members.view',
            ])
            ->get()
            ->each(fn (Permission $permission) => RolePermission::query()->firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ]));

        MemberRoleAssignment::create([
            'member_id' => $user->member_id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'status' => 'active',
        ]);
    }
}
