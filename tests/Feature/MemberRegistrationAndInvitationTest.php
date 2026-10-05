<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberRegistrationAndInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_member_and_links_user(): void
    {
        $this->post(route('register.attempt'), [
            'username' => 'jeanforte',
            'full_name' => 'Jean Forte',
            'sex' => 'male',
            'email' => 'jean@example.com',
            'phone' => '+243812345678',
            'birth_date' => '1990-01-15',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $member = Member::query()->where('email', 'jean@example.com')->firstOrFail();
        $user = User::query()->where('email', 'jean@example.com')->firstOrFail();

        $this->assertSame('Jean', $member->first_name);
        $this->assertSame('Forte', $member->last_name);
        $this->assertSame($member->id, $user->member_id);
        $this->assertDatabaseHas('memberships', ['member_id' => $member->id, 'type' => 'church', 'status' => 'pending']);
    }

    public function test_existing_member_email_is_reused_without_duplication(): void
    {
        $existingMember = Member::create([
            'first_name' => 'Marie',
            'last_name' => 'Ngoyi',
            'name' => 'Marie Ngoyi',
            'email' => 'marie@example.com',
            'phone' => '+243812345679',
            'sex' => 'female',
        ]);

        $this->post(route('register.attempt'), [
            'username' => 'mariengoyi',
            'full_name' => 'Marie Ngoyi',
            'sex' => 'female',
            'email' => 'marie@example.com',
            'phone' => '+243812345679',
            'birth_date' => '1988-10-20',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertSame(1, Member::query()->where('email', 'marie@example.com')->count());
        $user = User::query()->where('email', 'marie@example.com')->firstOrFail();
        $this->assertSame($existingMember->id, $user->member_id);
    }

    public function test_pending_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => 'pending',
            'member_id' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/en-attente');
    }

    public function test_invitation_creates_user_and_links_member(): void
    {
        $member = Member::create([
            'first_name' => 'Luc',
            'last_name' => 'Kalonji',
            'name' => 'Luc Kalonji',
            'email' => 'luc@example.com',
            'phone' => '+243812345699',
            'sex' => 'male',
        ]);

        $invitation = MemberInvitation::create([
            'member_id' => $member->id,
            'email' => 'luc@example.com',
            'token_hash' => Hash::make('secret-token'),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $token = 'secret-token';
        $user = $invitation->accept($token, [
            'username' => 'luckalonji',
            'password' => 'password123',
            'full_name' => 'Luc Kalonji',
        ]);

        $this->assertNotNull($user);
        $this->assertSame($member->id, $user->member_id);
        $this->assertSame('used', $invitation->fresh()->status);
    }
}
