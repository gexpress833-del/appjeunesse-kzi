<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchAndPastorRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_belong_to_a_church_and_be_a_pastor_principal(): void
    {
        $church = Church::create([
            'name' => 'La Parole Éternelle Kolwezi',
            'slug' => 'la-parole-eternelle-kolwezi',
            'type' => 'main',
            'status' => 'active',
        ]);

        $pastor = User::factory()->create([
            'role' => 'pasteur_n1',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertTrue($pastor->isPastorPrincipal());
        $this->assertEquals($church->id, $pastor->church_id);
        $this->assertEquals('La Parole Éternelle Kolwezi', $pastor->church->name);
    }

    public function test_members_without_role_remain_church_members(): void
    {
        $church = Church::create([
            'name' => 'Église de l’Alliance',
            'slug' => 'eglise-de-l-alliance',
            'type' => 'main',
            'status' => 'active',
        ]);

        $member = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'church_id' => $church->id,
        ]);

        $this->assertTrue($member->isChurchMember());
        $this->assertFalse($member->isPastorPrincipal());
        $this->assertNotNull($member->church);
    }
}
