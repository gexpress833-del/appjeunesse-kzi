<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberUserIdentityArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_exist_without_user_and_user_can_be_linked_to_member(): void
    {
        $department = Department::query()->where('code', 'youth')->firstOrFail();

        $member = Member::create([
            'name' => 'David Mbuyi',
            'dept' => $department->name,
            'role' => 'user',
            'email' => 'david@example.com',
            'phone' => '+243812345678',
            'birth_date' => '2013-05-10',
        ]);

        $this->assertNull($member->user);

        $user = User::factory()->create([
            'email' => 'david@example.com',
            'status' => 'active',
            'member_id' => $member->id,
        ]);

        $this->assertSame($member->id, $user->member?->id);
        $this->assertSame($member->id, $user->member_id);
        $this->assertSame($member->id, $member->fresh()->user?->member_id);
    }

    public function test_member_without_user_keeps_identity_separate_from_account(): void
    {
        $department = Department::query()->where('code', 'ecodim')->firstOrFail();

        $member = Member::create([
            'name' => 'Enfant ECODIM',
            'dept' => $department->name,
            'role' => 'user',
            'email' => 'enfant@example.com',
            'birth_date' => '2015-02-03',
        ]);

        $this->assertNull($member->user);
        $this->assertDatabaseMissing('users', ['email' => 'enfant@example.com']);
    }
}
