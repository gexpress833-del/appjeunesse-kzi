<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'date',
    'description',
    'photo_url',
    'cloudinary_public_id',
    'created_by',
    'dept',
])]
class Event extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'attendances')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept', 'name');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())->orderBy('date');
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', now())->orderByDesc('date');
    }
}
