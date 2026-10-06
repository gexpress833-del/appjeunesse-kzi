<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalAppLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_dashboard_renders_global_loading_overlay(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('global-app-loader')
            ->assertSee('Chargement');
    }

    public function test_mobile_menu_toggle_is_a_client_side_button_without_global_loading(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<button type="button" data-no-loading onclick="window.toggleSidebar()"', false)
            ->assertDontSee('<a href="#" aria-label="Ouvrir le menu"', false);
    }

    public function test_forbidden_access_shows_clear_unauthorized_page(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('ecodim.attendances.pick'))
            ->assertForbidden()
            ->assertSee('Accès non autorisé')
            ->assertSee('Vous n’avez pas les autorisations nécessaires pour accéder à cette page.')
            ->assertSee('Cette section est réservée aux responsables autorisés d’ECODIM.')
            ->assertSee('Retour au portail')
            ->assertDontSee('Nous rencontrons une difficulté');
    }

    public function test_error_page_explains_the_issue_and_next_steps(): void
    {
        $this->get('/non-existent-route')->assertNotFound()
            ->assertSee('Nous rencontrons une difficulté')
            ->assertSee('Retour à l’accueil')
            ->assertSee('Rechargez la page');
    }
}
