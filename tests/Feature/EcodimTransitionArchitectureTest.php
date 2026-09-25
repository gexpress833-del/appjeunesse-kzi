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
}
