<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transition_id',
    'old_status',
    'new_status',
    'changed_by',
    'reason',
])]
class EcodimTransitionHistory extends Model
{
    use HasFactory;

    public function transition(): BelongsTo
    {
        return $this->belongsTo(EcodimTransition::class, 'transition_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
