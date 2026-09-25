<?php

namespace Tests\Feature;

use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipRolePermissionArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_membership_and_role_assignment(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $membership = Membership::create([
            'user_id' => $user->id,
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
