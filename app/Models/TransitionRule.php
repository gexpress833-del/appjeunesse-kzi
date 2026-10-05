<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'source_space',
    'target_space',
    'name',
    'min_age',
    'max_age',
    'active_from',
    'active_to',
    'status',
])]
class TransitionRule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active_from' => 'date',
            'active_to' => 'date',
        ];
    }

    public function isApplicableOn(?CarbonInterface $date = null): bool
    {
        $date ??= now();

        if (strtolower((string) $this->status) !== 'active') {
            return false;
        }

        if ($this->active_from && $date->lt($this->active_from)) {
            return false;
        }

        if ($this->active_to && $date->gt($this->active_to)) {
            return false;
        }

        return true;
    }

    public function isEligibleFor(Member $member, ?CarbonInterface $date = null): bool
    {
        if (! $this->isApplicableOn($date)) {
            return false;
        }

        $age = $member->age();

        if ($age === null) {
            return false;
        }

        if ($this->min_age !== null && $age < (int) $this->min_age) {
            return false;
        }

        if ($this->max_age !== null && $age > (int) $this->max_age) {
            return false;
        }

        return true;
    }

    public function eligibilityDateFor(Member $member): ?CarbonInterface
    {
        if ($this->min_age === null || ! $member->birth_date) {
            return null;
        }

        $eligibilityDate = $member->birth_date->copy()->addYears((int) $this->min_age);

        return $this->isEligibleFor($member, $eligibilityDate) ? $eligibilityDate : null;
    }
}
