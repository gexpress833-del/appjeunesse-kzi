<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'level',
    'age_min',
    'age_max',
    'responsible_member_id',
    'status',
])]
class EcodimClass extends Model
{
    use HasFactory;

    public function responsibleMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_member_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EcodimClassMember::class, 'class_id');
    }
}
