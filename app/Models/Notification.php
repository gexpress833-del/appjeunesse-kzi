<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recipient_member_id',
    'type',
    'title',
    'message',
    'reference_type',
    'reference_id',
    'priority',
    'read_at',
])]
class Notification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_member_id');
    }
}
