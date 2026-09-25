<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
