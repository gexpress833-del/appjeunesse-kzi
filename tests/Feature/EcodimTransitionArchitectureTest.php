<?php

namespace Tests\Feature;

use App\Models\EcodimClass;
use App\Models\EcodimClassMember;
use App\Models\EcodimTransition;
use App\Models\EcodimTransitionHistory;
use App\Models\Member;
use App\Models\TransitionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcodimTransitionArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ecodim_member_can_be_assigned_to_class_and_transition_history_is_tracked(): void
    {
        $member = Member::create([
            'name' => 'Kenza Mbuyi',
            'sex' => 'female',
            'birth_date' => '2015-04-01',
            'email' => 'kenza@example.com',
        ]);

        $class = EcodimClass::create([
            'name' => 'Classe 3',
            'level' => 'intermediate',
            'age_min' => 10,
            'age_max' => 12,
            'status' => 'active',
        ]);

        EcodimClassMember::create([
            'class_id' => $class->id,
            'member_id' => $member->id,
            'status' => 'active',
        ]);

        $rule = TransitionRule::create([
            'name' => 'Transition ECODIM vers Jeunesse',
            'source_space' => 'ecodim',
            'target_space' => 'youth',
            'min_age' => 12,
            'max_age' => 14,
            'status' => 'active',
        ]);

        $transition = EcodimTransition::create([
            'member_id' => $member->id,
            'current_class_id' => $class->id,
            'eligibility_date' => now()->toDateString(),
            'status' => 'eligible',
        ]);

        EcodimTransitionHistory::create([
            'transition_id' => $transition->id,
            'old_status' => 'normal',
            'new_status' => 'eligible',
            'reason' => 'Age conforme',
        ]);

        $this->assertEquals('Classe 3', $class->name);
        $this->assertEquals('eligible', $transition->status);
        $this->assertTrue($rule->exists);
        $this->assertEquals('eligible', $transition->history()->latest()->first()->new_status);
    }

    public function test_transition_rule_evaluates_age_from_member_birth_date_and_keeps_the_same_member_identity_when_transferring_to_youth(): void
    {
        $member = Member::create([
            'name' => 'Aline Okafor',
            'sex' => 'female',
            'birth_date' => now()->subYears(13)->subMonths(2)->toDateString(),
            'email' => 'aline@example.com',
        ]);

        $class = EcodimClass::create([
            'name' => 'Classe 5',
            'level' => 'advanced',
            'age_min' => 12,
            'age_max' => 14,
            'status' => 'active',
        ]);

        $rule = TransitionRule::create([
            'name' => 'Transition ECODIM vers jeunesse',
            'source_space' => 'ecodim',
            'target_space' => 'youth',
            'min_age' => 12,
            'max_age' => 14,
            'status' => 'active',
        ]);

        $transition = EcodimTransition::query()->firstOrCreate([
            'member_id' => $member->id,
        ], [
            'current_class_id' => $class->id,
            'status' => 'normal',
        ]);

        $transition->syncEligibilityFromRule($rule);

        $this->assertSame($member->id, $transition->member_id);
        $this->assertTrue($rule->isEligibleFor($member));
        $this->assertSame('eligible', $transition->status);
        $this->assertNotNull($transition->eligibility_date);
        $this->assertSame($member->id, $transition->member->id);

        $transition->transferToYouth();

        $this->assertTrue($member->memberships()->where('type', 'youth')->where('status', 'active')->exists());
        $this->assertSame('transferred', $transition->fresh()->status);
        $this->assertSame('transferred', $transition->history()->latest()->first()->new_status);
    }
}
