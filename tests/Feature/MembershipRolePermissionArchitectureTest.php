<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MembershipRolePermissionArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_without_user_can_have_membership(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'church'], ['name' => 'Church']);

        $member = Member::factory()->create([
            'name' => 'Membre sans compte',
            'dept' => $department->name,
            'email' => 'sanscompte@example.com',
        ]);

        $membership = Membership::create([
            'member_id' => $member->id,
            'type' => 'church',
            'entity_id' => 1,
            'status' => 'active',
        ]);

        $this->assertTrue($membership->exists);
        $this->assertDatabaseHas('memberships', ['member_id' => $member->id, 'type' => 'church']);
        $this->assertFalse(Schema::hasColumn('memberships', 'user_id'));
    }

    public function test_user_linked_to_member_finds_its_memberships(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'youth'], ['name' => 'Portail jeunesse']);

        $member = Member::factory()->create([
            'name' => 'Membre lié',
            'dept' => $department->name,
            'email' => 'lie@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $member->id,
        ]);

        Membership::create([
            'member_id' => $member->id,
            'type' => 'youth',
            'entity_id' => 7,
            'status' => 'active',
        ]);

        $this->assertSame($member->id, $user->member_id);
        $this->assertTrue(Membership::query()->where('member_id', $user->member_id)->exists());
        $this->assertSame(['youth'], Membership::query()->where('member_id', $user->member_id)->pluck('type')->all());
    }

    public function test_user_without_member_id_has_no_memberships(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => null,
        ]);

        $this->assertSame([], $user->portalAccesses());
        $this->assertFalse($user->canAccessPortal('church'));
        $this->assertFalse($user->belongsToPortal('youth'));
        $this->assertFalse(Membership::query()->where('member_id', $user->member_id)->exists());
    }

    public function test_technical_account_receives_no_membership_automatically(): void
    {
        $user = User::factory()->create([
            'role' => 'secretariat',
            'status' => 'active',
            'member_id' => null,
        ]);

        $this->assertSame([], $user->portalAccesses());
        $this->assertFalse($user->belongsToPortal('ecodim'));
        $this->assertTrue($user->canAccessPortal('church'));
    }

    public function test_role_assignments_are_bound_to_member_identity_not_user_subject(): void
    {
        $member = Member::factory()->create([
            'name' => 'Parent assigné',
            'email' => 'parent-assigne@example.com',
        ]);

        $actor = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'member_id' => null,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $member->id,
        ]);

        $role = Role::create([
            'name' => 'Responsable Jeunesse',
            'slug' => 'responsable_jeunesse',
            'status' => 'active',
        ]);

        $assignment = MemberRoleAssignment::create([
            'member_id' => $member->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => 10,
            'status' => 'active',
            'assigned_by' => $actor->id,
        ]);

        $this->assertSame($member->id, $assignment->member_id);
        $this->assertSame($actor->id, $assignment->assigned_by);
        $this->assertTrue($user->portalRoleAssignments('youth')->contains('id', $assignment->id));
        $this->assertDatabaseHas('member_role_assignments', ['member_id' => $member->id, 'assigned_by' => $actor->id]);
    }

    public function test_memberships_are_bound_to_members_not_users(): void
    {
        $this->assertTrue(Schema::hasColumn('memberships', 'member_id'));
        $this->assertFalse(Schema::hasColumn('memberships', 'user_id'));

        $member = Member::factory()->create();
        Membership::create([
            'member_id' => $member->id,
            'type' => 'church',
            'entity_id' => 1,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('memberships', ['member_id' => $member->id]);
        $this->assertDatabaseMissing('memberships', ['user_id' => $member->id]);
    }

    public function test_member_id_is_required_for_memberships(): void
    {
        $columns = DB::select('PRAGMA table_info("memberships")');
        $memberColumn = collect($columns)->firstWhere('name', 'member_id');

        $this->assertNotNull($memberColumn);
        $this->assertSame(1, $memberColumn->notnull);
    }

    public function test_membership_foreign_key_points_to_members_table(): void
    {
        $foreignKeys = DB::select('PRAGMA foreign_key_list("memberships")');

        $this->assertNotEmpty($foreignKeys);
        $this->assertTrue(collect($foreignKeys)->contains(fn ($foreignKey) => $foreignKey->table === 'members' && $foreignKey->from === 'member_id'));
    }

    public function test_user_can_have_membership_and_role_assignment(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);

        $member = Member::factory()->create([
            'name' => 'Test Member',
            'dept' => $department->name,
            'email' => 'test@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $member->id,
        ]);

        $membership = Membership::create([
            'member_id' => $member->id,
            'type' => 'church',
            'entity_id' => 1,
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'Responsable Jeunesse',
            'slug' => 'responsable_jeunesse',
            'status' => 'active',
        ]);

        $permission = Permission::create([
            'name' => 'Voir les jeunes',
            'slug' => 'youth.view',
            'status' => 'active',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $assignment = MemberRoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'youth',
            'scope_id' => 7,
            'status' => 'active',
            'assigned_by' => $user->id,
        ]);

        $this->assertTrue($membership->exists);
        $this->assertTrue($assignment->exists);
        $this->assertTrue($role->permissions()->exists());
        $this->assertSame('Responsable Jeunesse', $assignment->role->name);
    }
}
