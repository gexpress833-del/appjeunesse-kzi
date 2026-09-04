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
    'dept',
    'role',
    'phone',
    'email',
    'birth_date',
    'address',
    'profile_photo_url',
    'notes',
])]
class Member extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function isInDepartment(string $department): bool
    {
        return $this->dept === $department;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept', 'name');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function socialVisits(): HasMany
    {
        return $this->hasMany(SocialVisit::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'attendances')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }
}
