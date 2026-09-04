<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialVisitAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_view_only_their_social_visits(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('social-visits.index'))
            ->assertOk();
    }

    public function test_member_dashboard_exposes_personal_features(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ma progression')
            ->assertSee('Mes visites')
            ->assertSee('Mon profil')
            ->assertSee('Annonces')
            ->assertSee('Annuaire')
            ->assertSee('Galerie')
            ->assertSee(route('members.index'), false)
            ->assertSee(route('gallery.index'), false)
            ->assertSee(route('social-visits.index'), false)
            ->assertSee(route('profile.edit'), false);
    }

    public function test_dashboard_cards_and_home_link_use_distinct_pages(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('dashboard.bilan'), false)
            ->assertSee(route('social-visits.index'), false)
            ->assertSee(route('members.index'), false)
            ->assertSee(route('gallery.index'), false)
            ->assertSee(route('home'), false);
    }

    public function test_non_social_responsable_cannot_manage_social_visits(): void
    {
        $user = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Chorale',
        ]);

        $this->actingAs($user)
            ->get(route('social-visits.create'))
            ->assertForbidden();
    }

    public function test_social_responsable_can_open_visit_form(): void
    {
        $user = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Social',
        ]);

        $this->actingAs($user)
            ->get(route('social-visits.create'))
            ->assertOk()
            ->assertSee('Planifier une visite sociale');
    }
}
