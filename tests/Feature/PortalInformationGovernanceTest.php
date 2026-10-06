<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EcodimClass;
use App\Models\Event;
use App\Models\HomeContent;
use App\Models\Member;
use App\Models\MemberRoleAssignment;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInformationGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_without_membership_can_view_only_the_selected_portal_announcements(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $portalRoutes = [
            'church' => 'dashboard',
            'youth' => 'dashboard.youth',
            'ecodim' => 'dashboard.ecodim',
        ];

        foreach (['church', 'youth', 'ecodim'] as $portal) {
            HomeContent::create([
                'source' => $portal,
                'type' => 'event_banner',
                'title' => "Annonce {$portal}",
                'content' => "Contenu {$portal}",
                'is_active' => true,
            ]);
        }

        HomeContent::create([
            'source' => 'ecodim',
            'type' => 'event_banner',
            'title' => 'Annonce ECODIM masquée',
            'content' => 'Contenu inactif',
            'is_active' => false,
        ]);

        foreach ($portalRoutes as $portal => $route) {
            $response = $this->actingAs($user)->get(route($route));

            $response->assertOk()
                ->assertSee("Annonce {$portal}")
                ->assertDontSee('Annonce ECODIM masquée');
            foreach (array_diff(array_keys($portalRoutes), [$portal]) as $otherPortal) {
                $response->assertDontSee("Annonce {$otherPortal}");
            }

            $this->assertTrue($user->canViewPortalInformation($portal));
            $this->assertFalse($user->canAccessPortal($portal));
        }

        $this->assertSame([], $user->portalAccesses());
        $this->assertSame(
            [
                $user->portalDashboardRouteName($user->currentPortal()),
                $user->portalAnnouncementsRouteName($user->currentPortal()),
            ],
            collect($user->portalNavigationItems())->pluck('route')->all(),
        );
    }

    public function test_informative_user_cannot_view_ecodim_children_or_use_business_permissions(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $child = Member::factory()->create(['name' => 'Enfant confidentiel']);
        Membership::create([
            'member_id' => $child->id,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'active',
        ]);
        $formerChild = Member::factory()->create(['name' => 'Ancien dossier ECODIM']);
        Membership::create([
            'member_id' => $formerChild->id,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'inactive',
        ]);
        $class = EcodimClass::create(['name' => 'Classe confidentielle', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('members.index'))
            ->assertOk()
            ->assertDontSee($child->name)
            ->assertDontSee($formerChild->name);

        $this->actingAs($user)->get(route('members.show', $child))->assertForbidden();

        $this->assertFalse($user->hasEcodimPermission('ecodim.members.view'));
        $this->assertFalse($user->canUsePortal('ecodim', 'ecodim.classes.manage'));
        $this->assertFalse($user->can('viewAny', EcodimClass::class));
        $this->assertFalse($user->can('manage', $class));

        $this->actingAs($user)->get(route('ecodim.attendances.pick'))->assertForbidden();
        $this->actingAs($user)->get(route('ecodim.events.create'))->assertForbidden();
        $this->actingAs($user)->get(route('ecodim.events.index'))->assertForbidden();
        $event = Event::create([
            'name' => 'Activité confidentielle',
            'date' => now()->addDay(),
            'created_by' => 'admin',
            'portal' => 'ecodim',
        ]);
        $this->actingAs($user)->post(route('ecodim.attendances.store', $event), [])->assertForbidden();
        $this->actingAs($user)->post(route('ecodim.events.store'), [])->assertForbidden();
        $this->actingAs($user)->get('/portail-ecodim/enfants')->assertNotFound();
        $this->actingAs($user)->post('/portail-ecodim/classes', [])->assertNotFound();
        $this->actingAs($user)->post('/portail-ecodim/transitions', [])->assertNotFound();
    }

    public function test_department_and_non_primary_admin_roles_do_not_grant_ecodim_access(): void
    {
        $departmentOnlyUser = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'ECODIM',
            'status' => 'active',
        ]);
        $churchAdmin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $youthRoleUser = User::factory()->create(['role' => 'responsable_jeunesse', 'status' => 'active']);
        $youthMember = Member::factory()->create(['name' => 'Responsable jeunesse ECODIM']);
        $youthRoleUser->update(['member_id' => $youthMember->id]);
        Membership::create(['member_id' => $youthMember->id, 'type' => 'youth', 'status' => 'active']);
        $youthRole = Role::query()->firstOrCreate(
            ['slug' => 'responsable_jeunesse'],
            ['name' => 'Responsable jeunesse', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $youthRoleUser->id,
            'member_id' => $youthMember->id,
            'role_id' => $youthRole->id,
            'scope_type' => 'youth',
            'status' => 'active',
        ]);
        $ecodimRoleUser = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $ecodimRole = Role::query()->firstOrCreate(
            ['slug' => 'ecodim_manager'],
            ['name' => 'Responsable ECODIM', 'status' => 'active'],
        );
        MemberRoleAssignment::create([
            'user_id' => $ecodimRoleUser->id,
            'role_id' => $ecodimRole->id,
            'scope_type' => 'ecodim',
            'status' => 'active',
        ]);

        foreach ([$departmentOnlyUser, $churchAdmin, $youthRoleUser, $ecodimRoleUser] as $user) {
            $this->assertFalse($user->canGovernPortal('ecodim'));
            $this->assertFalse($user->canUsePortal('ecodim', 'ecodim.classes.manage'));
            $this->assertFalse($user->hasEcodimPermission('ecodim.classes.manage'));
        }

        $this->assertFalse($departmentOnlyUser->belongsToPortal('ecodim'));
        $this->assertFalse($departmentOnlyUser->canManageAttendance('church', 'ECODIM'));
        $this->actingAs($churchAdmin)->get(route('dashboard.ecodim'))->assertOk();
        $this->actingAs($churchAdmin)->get(route('ecodim.attendances.pick'))->assertForbidden();
    }

    public function test_users_without_youth_permission_see_clear_unauthorized_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('youth.attendances.pick'))
            ->assertForbidden()
            ->assertSee('Accès non autorisé')
            ->assertSee('Cette section est réservée aux responsables autorisés de la Jeunesse.');
    }

    public function test_users_without_church_permission_see_clear_unauthorized_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('attendances.pick'))
            ->assertForbidden()
            ->assertSee('Accès non autorisé')
            ->assertSee('Cette section est réservée aux utilisateurs disposant des autorisations requises.');
    }

    public function test_active_primary_admin_governs_all_portals_without_ecodim_membership_or_role(): void
    {
        $primaryAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => true,
        ]);

        foreach (['church', 'youth', 'ecodim'] as $portal) {
            $this->assertTrue($primaryAdmin->canViewPortalInformation($portal));
            $this->assertTrue($primaryAdmin->canGovernPortal($portal));
            $this->actingAs($primaryAdmin)->get(route(match ($portal) {
                'church' => 'dashboard',
                'youth' => 'dashboard.youth',
                default => 'dashboard.ecodim',
            }))->assertOk();
        }

        $this->assertFalse($primaryAdmin->hasActiveEcodimMembership());
        $this->assertFalse($primaryAdmin->canAccessPortal('ecodim'));
        $this->assertFalse($primaryAdmin->canUsePortal('ecodim', 'governance.supervise'));
        $this->assertFalse($primaryAdmin->hasEcodimPermission('ecodim.classes.manage'));
        $this->assertFalse($primaryAdmin->portalRoleAssignments('ecodim')->isNotEmpty());
        $this->assertSame([], $primaryAdmin->portalAccesses());
    }

    public function test_primary_admin_can_review_ecodim_sensitive_data_and_attendance_without_business_role(): void
    {
        $primaryAdmin = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'is_primary_admin' => true,
        ]);
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $child = Member::factory()->create(['name' => 'Enfant gouvernance ECODIM']);
        Membership::create([
            'member_id' => $child->id,
            'type' => 'ecodim',
            'entity_id' => $department->id,
            'status' => 'active',
        ]);
        EcodimClass::create(['name' => 'Classe gouvernance', 'status' => 'active']);

        $this->actingAs($primaryAdmin)
            ->get(route('members.index'))
            ->assertOk()
            ->assertSee($child->name);

        $this->actingAs($primaryAdmin)->get(route('ecodim.attendances.pick'))->assertOk();
        $this->assertFalse($primaryAdmin->canManageAttendance('ecodim', 'Classe gouvernance'));
        $this->actingAs($primaryAdmin)->get(route('ecodim.events.create'))->assertForbidden();
        $this->assertFalse($primaryAdmin->hasEcodimPermission('ecodim.attendance.manage'));
        $this->assertFalse($primaryAdmin->hasPortalRole('ecodim', 'ecodim_manager'));
        $this->assertFalse($primaryAdmin->hasActiveEcodimMembership());
    }

    public function test_ecodim_responsible_keeps_only_its_assigned_class_scope(): void
    {
        $department = Department::query()->firstOrCreate(['code' => 'ecodim'], ['name' => 'ECODIM']);
        $member = Member::factory()->create(['name' => 'Responsable ECODIM']);
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
        $assignedClass = EcodimClass::create(['name' => 'Classe autorisée', 'status' => 'active']);
        $otherClass = EcodimClass::create(['name' => 'Classe interdite', 'status' => 'active']);
        $role = Role::query()->where('slug', 'ecodim_class_responsible')->firstOrFail();
        $permission = Permission::query()->where('slug', 'ecodim.attendance.manage')->firstOrFail();
        RolePermission::query()->create(['role_id' => $role->id, 'permission_id' => $permission->id]);
        MemberRoleAssignment::query()->create([
            'member_id' => $member->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'ecodim_class',
            'scope_id' => $assignedClass->id,
            'status' => 'active',
        ]);

        $this->assertTrue($user->canUsePortal('ecodim', 'ecodim.attendance.manage', $assignedClass));
        $this->assertFalse($user->canUsePortal('ecodim', 'ecodim.attendance.manage', $otherClass));
    }

    public function test_inactive_primary_admin_and_non_primary_admin_do_not_receive_global_governance(): void
    {
        $inactivePrimaryAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'inactive',
            'is_primary_admin' => true,
        ]);
        $ordinaryAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => false,
        ]);

        $this->assertFalse($inactivePrimaryAdmin->canGovernPortal('ecodim'));
        $this->assertFalse($ordinaryAdmin->canGovernPortal('ecodim'));
        $this->assertFalse($ordinaryAdmin->canUsePortal('ecodim', 'governance.supervise'));
    }
}
