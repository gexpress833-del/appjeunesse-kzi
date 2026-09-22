<?php

namespace Tests\Feature;

use App\Models\Member;
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

    public function test_member_cannot_update_sex_from_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'member.profile@example.com',
        ]);
        $member = Member::create([
            'name' => 'Membre Profil',
            'sex' => 'male',
            'email' => $user->email,
            'role' => 'Fidèle',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'dept' => '',
                'sex' => 'female',
                'birth_date' => '',
                'address' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'sex' => 'male',
        ]);
    }

    public function test_user_and_responsable_cannot_change_their_assigned_department_from_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'dept' => 'Chorale',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'dept' => 'Social',
                'birth_date' => '',
                'address' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'dept' => 'Chorale',
        ]);

        $responsable = User::factory()->create([
            'role' => 'responsable',
            'status' => 'active',
            'dept' => 'Médias/DCC',
        ]);

        $this->actingAs($responsable)
            ->put(route('profile.update'), [
                'full_name' => $responsable->full_name,
                'phone' => $responsable->phone,
                'dept' => 'Social',
                'birth_date' => '',
                'address' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $responsable->id,
            'dept' => 'Médias/DCC',
        ]);
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
