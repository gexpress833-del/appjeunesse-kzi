<?php

namespace Tests\Feature;

use App\Models\Department;
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

    public function test_admin_can_assign_a_user_role_and_department(): void
    {
        Department::create(['name' => 'Social']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.role', $user), [
                'role' => 'responsable',
                'dept' => 'Social',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'responsable',
            'dept' => 'Social',
        ]);
    }
}
