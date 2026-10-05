<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FamilyTaskNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_members_can_be_linked_and_tasks_notifications_created(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
        ]);

        $member = Member::create([
            'name' => 'Aimé Kabeya',
            'sex' => 'male',
            'birth_date' => '2012-01-10',
            'email' => 'aime@example.com',
        ]);

        $family = Family::create([
            'name' => 'Famille Kabeya',
            'status' => 'active',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $member->id,
            'relationship_type' => 'child',
            'is_primary_contact' => true,
            'status' => 'active',
        ]);

        $task = Task::create([
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Examiner la transition ECODIM → Jeunesse',
            'type' => 'transition',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $notification = Notification::create([
            'recipient_member_id' => $user->id,
            'type' => 'transition',
            'title' => 'Transition ECODIM → Jeunesse',
            'message' => 'Un jeune est éligible à la transition.',
            'priority' => 'high',
        ]);

        $this->assertTrue($family->exists);
        $this->assertEquals('Famille Kabeya', $family->name);
        $this->assertEquals('Examiner la transition ECODIM → Jeunesse', $task->title);
        $this->assertEquals('Transition ECODIM → Jeunesse', $notification->title);
    }

    public function test_family_links_are_member_based_and_do_not_require_user_accounts(): void
    {
        $family = Family::create([
            'name' => 'Famille Nkulu',
            'status' => 'active',
        ]);

        $child = Member::factory()->create([
            'name' => 'Enfant sans compte',
            'email' => 'enfant@example.com',
        ]);

        $guardian = Member::factory()->create([
            'name' => 'Parent sans compte',
            'email' => 'parent@example.com',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $guardian->id,
            'relationship_type' => 'guardian',
            'is_primary_contact' => true,
            'status' => 'active',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $child->id,
            'relationship_type' => 'child',
            'status' => 'active',
        ]);

        $this->assertFalse(Schema::hasColumn('family_members', 'user_id'));
        $this->assertSame([$child->id, $guardian->id], $family->familyMembers()->pluck('member_id')->sort()->values()->all());
        $this->assertFalse($child->user()->exists());
        $this->assertFalse($guardian->user()->exists());
    }

    public function test_user_accounts_can_be_linked_to_parent_members_without_breaking_family_identity(): void
    {
        $family = Family::create([
            'name' => 'Famille Mbuyi',
            'status' => 'active',
        ]);

        $parentMember = Member::factory()->create([
            'name' => 'Parent connecté',
            'email' => 'parent-connecte@example.com',
        ]);

        $childMember = Member::factory()->create([
            'name' => 'Enfant connecté',
            'email' => 'enfant-connecte@example.com',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'member_id' => $parentMember->id,
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $parentMember->id,
            'relationship_type' => 'guardian',
            'is_primary_contact' => true,
            'status' => 'active',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $childMember->id,
            'relationship_type' => 'child',
            'status' => 'active',
        ]);

        $this->assertSame($parentMember->id, $user->member_id);
        $this->assertSame('guardian', $family->familyMembers()->where('member_id', $parentMember->id)->first()->relationship_type);
        $this->assertTrue($parentMember->families()->where('families.id', $family->id)->exists());
        $this->assertTrue($childMember->families()->where('families.id', $family->id)->exists());
    }

    public function test_family_links_do_not_grant_portal_access_and_keep_member_identity_after_user_removal(): void
    {
        $family = Family::create([
            'name' => 'Famille Lelo',
            'status' => 'active',
        ]);

        $member = Member::factory()->create([
            'name' => 'Membre familial',
            'email' => 'membre-familial@example.com',
        ]);

        $child = Member::factory()->create([
            'name' => 'Enfant familial',
            'email' => 'enfant-familial@example.com',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $member->id,
            'relationship_type' => 'guardian',
            'status' => 'active',
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'member_id' => $child->id,
            'relationship_type' => 'child',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'inactive',
            'member_id' => $member->id,
        ]);

        $this->assertSame([], $user->portalAccesses());
        $this->assertFalse($user->canAccessPortal('ecodim'));

        $user->delete();

        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertDatabaseHas('family_members', ['member_id' => $member->id, 'family_id' => $family->id]);
        $this->assertDatabaseHas('family_members', ['member_id' => $child->id, 'family_id' => $family->id]);
    }
}
