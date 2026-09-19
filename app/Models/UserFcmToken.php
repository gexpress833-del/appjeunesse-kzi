<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function registerForUser(User $user, string $token, ?string $device = 'web'): self
    {
        return static::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'token' => $token,
            ],
            [
                'device' => $device ?? 'web',
                'last_used_at' => now(),
            ]
        );
    }
}
