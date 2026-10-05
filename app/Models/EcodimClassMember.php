<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Date;

#[Fillable([
    'class_id',
    'member_id',
    'starts_at',
    'ends_at',
    'status',
])]
class EcodimClassMember extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $classMember): void {
            if ($classMember->status !== 'active'
                || ($classMember->starts_at && $classMember->starts_at->isFuture())
                || ($classMember->ends_at && $classMember->ends_at->isPast())) {
                return;
            }

            $departmentId = Department::query()->where('code', 'ecodim')->value('id');

            Membership::query()->updateOrCreate(
                ['member_id' => $classMember->member_id, 'type' => 'ecodim'],
                [
                    'entity_id' => $departmentId,
                    'status' => 'active',
                    'starts_at' => $classMember->starts_at ?? Date::now(),
                    'ends_at' => $classMember->ends_at,
                ],
            );
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(EcodimClass::class, 'class_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
