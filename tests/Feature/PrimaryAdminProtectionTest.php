<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrimaryAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_a_protected_primary_admin(): void
    {
        $this->seed(AdminUserSeeder::class);

        $primaryAdmin = User::where('email', 'admin@laparoleeternelle.com')->firstOrFail();

        $this->assertTrue($primaryAdmin->isPrimaryAdmin());
        $this->assertSame('admin', $primaryAdmin->role);
        $this->assertSame('active', $primaryAdmin->status);
    }

    public function test_other_admin_cannot_change_primary_admin_role_or_status(): void
    {
        $primaryAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => true,
        ]);
        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($otherAdmin)
            ->patch(route('users.role', $primaryAdmin), ['role' => 'user'])
            ->assertForbidden();

        $this->actingAs($otherAdmin)
            ->patch(route('users.status', $primaryAdmin), ['status' => 'inactive'])
            ->assertForbidden();

        $primaryAdmin->refresh();
        $this->assertSame('admin', $primaryAdmin->role);
        $this->assertSame('active', $primaryAdmin->status);
    }

    public function test_primary_admin_cannot_be_deleted(): void
    {
        $primaryAdmin = User::factory()->create(['is_primary_admin' => true]);

        $this->expectException(AuthorizationException::class);

        $primaryAdmin->delete();
    }
}
