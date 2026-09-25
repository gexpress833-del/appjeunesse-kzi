<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_secretariat_can_open_and_update_operational_settings(): void
    {
        Department::create(['name' => 'Jeunesse']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $secretariat = User::factory()->create(['role' => 'secretariat', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Paramètres');

        $this->actingAs($secretariat)
            ->put(route('settings.update'), [
                'phone' => '+243 900 000 000',
                'email' => 'contact@example.com',
                'address' => 'Kolwezi',
                'attendance_statuses' => ['present', 'absent'],
                'attendance_editable_hours' => 24,
                'carousel_enabled' => '1',
                'events_enabled' => '1',
                'video_comments_enabled' => '1',
                'video_likes_enabled' => '1',
                'new_registration' => '1',
                'event_created' => '1',
                'attendance_recorded' => '1',
                'social_visit' => '1',
                'video_comment' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(['present', 'absent'], AppSetting::current()->attendance_statuses);
        $this->assertSame(24, AppSetting::current()->attendance_editable_hours);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'church_name' => 'Église renouvelée',
                'application_name' => 'jeunesse-lpk',
                'timezone' => 'Africa/Lubumbashi',
                'phone' => '+243 900 000 001',
                'email' => 'admin@example.com',
                'address' => 'Nouvelle adresse',
                'attendance_statuses' => ['present', 'absent', 'late', 'excused'],
                'attendance_editable_hours' => 48,
            ])
            ->assertRedirect();

        $this->assertSame('jeunesse-lpk', AppSetting::current()->application_name);
    }

    public function test_department_rename_updates_related_records_and_deletion_protects_used_departments(): void
    {
        $department = Department::create(['name' => 'Médias']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active', 'dept' => 'Médias']);
        $member = Member::create(['name' => 'Membre média', 'dept' => 'Médias', 'role' => 'Membre']);
        $event = Event::create(['name' => 'Événement média', 'date' => now()->addDay(), 'dept' => 'Médias', 'created_by' => 'admin']);

        $this->actingAs($admin)
            ->put(route('settings.departments.update', $department), ['name' => 'Médias/DCC'])
            ->assertRedirect();

        $this->assertDatabaseHas('members', ['id' => $member->id, 'dept' => 'Médias/DCC']);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'dept' => 'Médias/DCC']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'dept' => 'Médias/DCC']);

        $this->actingAs($admin)
            ->delete(route('settings.departments.destroy', $department->fresh()))
            ->assertSessionHasErrors('department');
    }

    public function test_church_administrators_can_rename_departments(): void
    {
        $department = Department::create(['name' => 'Jeunesse']);
        $roles = ['admin', 'secretariat', 'pasteur_n1'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->put(route('settings.departments.update', $department), ['name' => 'Jeunesse active'])
                ->assertRedirect();

            $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Jeunesse active']);

            $department->update(['name' => 'Jeunesse']);
        }
    }

    public function test_maintenance_mode_blocks_active_members_but_not_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);
        AppSetting::current()->update(['maintenance_mode' => true]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertStatus(503)
            ->assertSee('Application temporairement indisponible');

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk();
    }
}
