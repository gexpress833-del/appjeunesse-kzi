<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'assigned_to',
    'created_by',
    'title',
    'description',
    'type',
    'priority',
    'status',
    'due_at',
    'reference_type',
    'reference_id',
])]
class Task extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
