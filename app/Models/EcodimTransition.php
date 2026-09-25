<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(EcodimTransitionHistory::class, 'transition_id');
    }
}
