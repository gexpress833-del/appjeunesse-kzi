<?php

namespace App\Models;

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
}
