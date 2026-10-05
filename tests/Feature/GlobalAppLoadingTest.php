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

    public function test_error_page_explains_the_issue_and_next_steps(): void
    {
        $this->get('/non-existent-route')->assertNotFound()
            ->assertSee('Nous rencontrons une difficulté')
            ->assertSee('Retour à l’accueil')
            ->assertSee('Rechargez la page');
    }
}
