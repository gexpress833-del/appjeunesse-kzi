<?php

namespace Tests\Feature;

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
    }
}
