<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_primary_admin_cannot_remove_own_admin_rights_or_deactivate_self(): void
    {
        $primaryAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'is_primary_admin' => true,
        ]);

        $this->actingAs($primaryAdmin)
            ->patch(route('users.role', $primaryAdmin), ['role' => 'user'])
            ->assertForbidden();

        $this->actingAs($primaryAdmin)
            ->patch(route('users.status', $primaryAdmin), ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $primaryAdmin->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_validating_an_account_notifies_the_member(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $member = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.validate', $member))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertTrue($member->fresh()->unreadNotifications->isNotEmpty());
    }

    public function test_validating_an_account_sends_a_brevo_email_when_configured(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.sender_email' => 'noreply@example.com',
            'services.brevo.sender_name' => 'appjeunesse-kzi',
        ]);
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'test-message'], 201)]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $member = User::factory()->create([
            'role' => 'user',
            'status' => 'pending',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($admin)->patch(route('users.validate', $member));

        Http::assertSent(function ($request) use ($member): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->header('api-key')[0] === 'test-brevo-key'
                && data_get($request->data(), 'to.0.email') === $member->email
                && data_get($request->data(), 'subject') === 'Votre compte appjeunesse-kzi est validé';
        });
    }
}
