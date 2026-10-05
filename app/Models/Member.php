<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'first_name',
    'last_name',
    'sex',
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

    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            $member->syncNameParts();
        });
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function syncNameParts(): void
    {
        $fullName = trim((string) ($this->name ?? ''));

        if ($fullName === '' && (filled($this->first_name) || filled($this->last_name))) {
            $fullName = trim((string) ($this->first_name ?? '').' '.(string) ($this->last_name ?? ''));
        }

        if (filled($fullName)) {
            $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $this->first_name = $parts[0] ?? '';
            $this->last_name = implode(' ', array_slice($parts, 1));
            $this->name = $fullName;

            return;
        }

        if (filled($this->first_name) || filled($this->last_name)) {
            $this->name = trim((string) ($this->first_name ?? '').' '.(string) ($this->last_name ?? ''));
        }
    }

    public function isInDepartment(string $department): bool
    {
        return $this->dept === $department;
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept', 'name');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'member_id');
    }

    public static function splitFullName(string $fullName): array
    {
        $trimmed = trim($fullName);

        if ($trimmed === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [$trimmed];

        return [
            $parts[0],
            implode(' ', array_slice($parts, 1)),
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function familyLinks(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_members')
            ->withPivot('relationship_type', 'is_primary_contact', 'status')
            ->withTimestamps();
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
