<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Department;
use App\Models\Event;
use App\Models\HomeContent;
use App\Models\MemberRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_active_church_member_has_access_to_the_church_and_youth_portals(): void
    {
        $church = Church::create([
            'name' => 'Église de test',
            'slug' => 'eglise-de-test',
            'type' => 'main',
            'status' => 'active',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertTrue($member->isChurchMember());
        $this->assertTrue($member->canAccessPortal('church'));
        $this->assertTrue($member->canAccessPortal('youth'));
        $this->assertSame(['church', 'youth', 'ecodim'], $member->portalAccesses());
        $this->assertSame('youth', $member->primaryPortal());
    }

    public function test_pastor_and_admin_users_prioritize_the_church_portal(): void
    {
        $church = Church::create([
            'name' => 'Église pastorale',
            'slug' => 'eglise-pastorale',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertTrue($pastor->canAccessPortal('church'));
        $this->assertTrue($pastor->canAccessPortal('youth'));
        $this->assertSame('church', $pastor->primaryPortal());
    }

    public function test_portal_navigation_key_separates_youth_and_church_modules(): void
    {
        $church = Church::create([
            'name' => 'Église des modules',
            'slug' => 'eglise-des-modules',
            'type' => 'main',
            'status' => 'active',
        ]);

        $youthUser = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $churchLeader = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertSame('youth', $youthUser->portalNavigationKey());
        $this->assertSame('church', $churchLeader->portalNavigationKey());
    }

    public function test_portal_navigation_items_match_the_active_module_set(): void
    {
        $church = Church::create([
            'name' => 'Église des modules',
            'slug' => 'eglise-des-modules-2',
            'type' => 'main',
            'status' => 'active',
        ]);

        $youthUser = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $churchLeader = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertContains('members.index', collect($youthUser->portalNavigationItems())->pluck('route')->all());
        $this->assertContains('settings.index', collect($churchLeader->portalNavigationItems())->pluck('route')->all());
        $this->assertContains('gallery.index', collect($churchLeader->portalNavigationItems())->pluck('route')->all());
        $this->assertContains('Portail jeunesse', collect($youthUser->portalNavigationItems())->pluck('label')->all());
    }

    public function test_pastor_can_view_the_gallery_from_the_church_portal(): void
    {
        $church = Church::create([
            'name' => 'Église de la galerie',
            'slug' => 'eglise-de-la-galerie',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->actingAs($pastor)
            ->get(route('gallery.index'))
            ->assertOk();
    }

    public function test_pastor_sees_the_requested_portal_and_personal_youth_dashboard(): void
    {
        $church = Church::create([
            'name' => 'Église du portail',
            'slug' => 'eglise-du-portail',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($pastor)->get('/portail-jeunesse');

        $response->assertOk();
        $response->assertViewIs('dashboard.personal');
        $response->assertSee('Portail jeunesse');
        $response->assertSee('Pasteur principal');
        $response->assertDontSee('Secrétaire');
    }

    public function test_pastor_sees_the_church_dashboard_with_the_pastoral_role_label(): void
    {
        $church = Church::create([
            'name' => 'Église du tableau',
            'slug' => 'eglise-du-tableau',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($pastor)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard.global');
        $response->assertSee('Pasteur principal');
        $response->assertSee('Direction pastorale');
        $response->assertDontSee('Bonjour Secrétaire');
    }

    public function test_member_can_enter_the_ecodim_portal(): void
    {
        $church = Church::create([
            'name' => 'Église ECODIM',
            'slug' => 'eglise-ecodim',
            'type' => 'main',
            'status' => 'active',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($member)->get(route('dashboard.ecodim'));

        $response->assertOk();
        $response->assertViewIs('dashboard.personal');
        $response->assertSee('Portail ECODIM');
        $this->assertSame('ecodim', $member->currentPortal());
        $this->assertSame('ecodim', $member->portalNavigationKey());
    }

    public function test_pastor_can_create_events_in_the_church_portal(): void
    {
        $church = Church::create([
            'name' => 'Église administrée',
            'slug' => 'eglise-administree',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->actingAs($pastor)
            ->get(route('events.create'))
            ->assertOk();

        $this->actingAs($pastor)
            ->post(route('events.store'), [
                'name' => 'Culte dominical',
                'date' => now()->addWeek()->format('Y-m-d H:i:s'),
                'description' => 'Culte de toute l’église.',
            ])
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'name' => 'Culte dominical',
            'created_by' => $pastor->username,
        ]);

        $this->actingAs($pastor)
            ->get(route('dashboard.youth'))
            ->assertOk();

        $this->actingAs($pastor)
            ->get(route('events.create'))
            ->assertOk()
            ->assertSee('Portail église');

        $this->assertInstanceOf(Event::class, Event::first());
    }

    public function test_content_sources_are_separated_by_role(): void
    {
        $church = Church::create([
            'name' => 'Église des sources',
            'slug' => 'eglise-des-sources',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create(['role' => 'pasteur_n1', 'church_id' => $church->id]);
        $youthLeader = User::factory()->create(['role' => 'user', 'church_id' => $church->id]);
        $youthRole = Role::create(['name' => 'Responsable jeunesse', 'slug' => 'responsable_jeunesse']);
        MemberRoleAssignment::create([
            'user_id' => $youthLeader->id,
            'role_id' => $youthRole->id,
            'scope_type' => 'youth',
        ]);

        $this->assertSame(['church'], $pastor->manageableContentSources());
        $this->assertSame(['youth'], $youthLeader->manageableContentSources());
        $this->assertFalse($pastor->canManageContentSource('youth'));
        $this->assertTrue($youthLeader->canManageContentSource('youth'));

        HomeContent::create([
            'source' => 'church',
            'type' => 'event_banner',
            'title' => 'Communication église',
            'content' => 'Annonce officielle',
        ]);
        HomeContent::create([
            'source' => 'youth',
            'type' => 'event_banner',
            'title' => 'Communication jeunesse',
            'content' => 'Annonce jeunesse',
        ]);

        $this->actingAs($pastor)->get(route('carousel.index'))->assertSee('Communication église')->assertDontSee('Communication jeunesse');
        $this->actingAs($youthLeader)->get(route('carousel.index'))->assertSee('Communication jeunesse')->assertDontSee('Communication église');
    }

    public function test_all_announcement_sources_are_visible_on_the_public_homepage(): void
    {
        foreach (['church' => 'Annonce église', 'youth' => 'Annonce jeunesse', 'ecodim' => 'Annonce ECODIM'] as $source => $title) {
            HomeContent::create([
                'source' => $source,
                'type' => 'event_banner',
                'title' => $title,
                'content' => 'Communication publique',
                'is_active' => true,
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Annonce église');
        $response->assertSee('Annonce jeunesse');
        $response->assertSee('Annonce ECODIM');
        $response->assertSee('Portail église');
        $response->assertSee('Portail jeunesse');
        $response->assertSee('ECODIM');
    }

    public function test_only_the_pastor_can_nominate_a_portal_department_leader(): void
    {
        $church = Church::create([
            'name' => 'Église des responsables',
            'slug' => 'eglise-des-responsables',
            'type' => 'main',
            'status' => 'active',
        ]);
        $pastor = User::factory()->create(['role' => 'pasteur_n1', 'church_id' => $church->id]);
        $secretariat = User::factory()->create(['role' => 'secretariat', 'church_id' => $church->id]);
        $leader = User::factory()->create(['role' => 'user', 'church_id' => $church->id]);
        $department = Department::where('code', 'youth')->firstOrFail();

        $this->actingAs($pastor)
            ->put(route('settings.departments.leader', $department), ['leader_user_id' => $leader->id])
            ->assertRedirect();

        $department->refresh();
        $leader->refresh();

        $this->assertSame($leader->id, $department->leader_user_id);
        $this->assertSame('Portail jeunesse', $leader->dept);
        $this->assertTrue($leader->canManageContentSource('youth'));
        $this->assertFalse($pastor->canManageContentSource('youth'));

        $this->actingAs($secretariat)
            ->put(route('settings.departments.leader', $department), ['leader_user_id' => $secretariat->id])
            ->assertForbidden();
    }

    public function test_pastor_cannot_manage_youth_or_dcc_media_actions(): void
    {
        $church = Church::create([
            'name' => 'Église des accès média',
            'slug' => 'eglise-des-acces-media',
            'type' => 'main',
            'status' => 'active',
        ]);
        $pastor = User::factory()->create(['role' => 'pasteur_n1', 'church_id' => $church->id]);

        $this->actingAs($pastor)->get(route('dashboard.youth'))->assertOk();
        $this->actingAs($pastor)->get(route('settings.index'))->assertOk();
        $this->actingAs($pastor)->get(route('carousel.index'))->assertOk();
        $this->actingAs($pastor)->get(route('gallery.upload'))->assertForbidden();
        $this->actingAs($pastor)->get(route('live.edit'))->assertForbidden();
        $this->actingAs($pastor)->get(route('videos.manage'))->assertForbidden();

        $this->actingAs($pastor)->get(route('dashboard'))->assertOk();
        $this->actingAs($pastor)->get(route('settings.index'))->assertOk();
        $this->actingAs($pastor)->get(route('gallery.upload'))->assertForbidden();
    }

    public function test_dcc_responsible_can_manage_media(): void
    {
        $church = Church::create([
            'name' => 'Église du DCC',
            'slug' => 'eglise-du-dcc',
            'type' => 'main',
            'status' => 'active',
        ]);
        $dcc = User::factory()->create([
            'role' => 'responsable',
            'dept' => 'DCC',
            'church_id' => $church->id,
        ]);

        $this->actingAs($dcc)->get(route('gallery.upload'))->assertOk();
    }
}
