<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

#[Fillable([
    'member_id',
    'current_class_id',
    'target_group_id',
    'eligibility_date',
    'status',
    'initiated_at',
    'reviewed_at',
    'reviewed_by',
    'transferred_at',
    'transferred_by',
    'postponement_reason',
    'notes',
])]
class EcodimTransition extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'eligibility_date' => 'date',
            'initiated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'transferred_at' => 'datetime',
        ];
    }

    public static function forMember(Member $member): ?self
    {
        return self::query()->where('member_id', $member->id)->latest()->first();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(EcodimClass::class, 'current_class_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(EcodimTransitionHistory::class, 'transition_id')->orderByDesc('id');
    }

    public function syncEligibilityFromRule(?TransitionRule $rule = null): self
    {
        $rule ??= TransitionRule::query()
            ->where('source_space', 'ecodim')
            ->where('target_space', 'youth')
            ->where('status', 'active')
            ->latest('updated_at')
            ->first();

        $oldStatus = $this->status ?? 'normal';
        $newStatus = 'normal';
        $eligibilityDate = null;

        if ($rule && $this->member()->exists()) {
            $eligibilityDate = $rule->eligibilityDateFor($this->member);

            if ($eligibilityDate !== null) {
                $newStatus = 'eligible';
            }
        }

        $this->eligibility_date = $eligibilityDate?->toDateString();
        $this->status = $newStatus;

        if ($oldStatus !== $newStatus) {
            $this->recordStatusChange($oldStatus, $newStatus, null, $newStatus === 'eligible' ? 'Age conforme' : 'Règle de transition non remplie');
        }

        $this->save();

        return $this->refresh();
    }

    public function recordStatusChange(string $oldStatus, string $newStatus, ?int $changedBy = null, ?string $reason = null): self
    {
        if ($oldStatus === $newStatus) {
            return $this;
        }

        EcodimTransitionHistory::create([
            'transition_id' => $this->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason' => $reason,
        ]);

        return $this;
    }

    public function transferToYouth(?User $actor = null): self
    {
        $youthDepartment = Department::query()->where('code', 'youth')->first();

        $previousStatus = $this->status ?? 'normal';
        $this->status = 'transferred';
        $this->transferred_by = $actor?->id ?? $this->transferred_by;
        $this->transferred_at = $this->transferred_at ?? Date::now();
        $this->save();

        if ($youthDepartment) {
            Membership::query()->updateOrCreate(
                ['member_id' => $this->member_id, 'type' => 'youth'],
                [
                    'entity_id' => $youthDepartment->id,
                    'status' => 'active',
                    'starts_at' => Date::now(),
                ],
            );
        }

        if ($previousStatus !== 'transferred') {
            $this->recordStatusChange($previousStatus, 'transferred', $actor?->id ?? null, 'Transfert vers le portail jeunesse');
        }

        return $this->refresh();
    }
}
