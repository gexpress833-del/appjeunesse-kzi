<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PasswordResetRequested;
use Database\Factories\UserFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'username',
    'full_name',
    'email',
    'password',
    'phone',
    'role',
    'status',
    'dept',
    'birth_date',
    'address',
    'profile_photo_url',
    'created_by',
    'role_assigned_by',
    'role_assigned_at',
    'notes',
    'is_primary_admin',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'role_assigned_at' => 'datetime',
            'is_primary_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if ($user->isPrimaryAdmin()) {
                throw new AuthorizationException('L’administrateur principal ne peut pas être supprimé.');
            }
        });
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->isRole('admin');
    }

    public function isPrimaryAdmin(): bool
    {
        return (bool) $this->is_primary_admin;
    }

    public function isSecretariat(): bool
    {
        return $this->isRole('secretariat');
    }

    public function isResponsable(): bool
    {
        return $this->isRole('responsable');
    }

    /**
     * Responsable du département Médias/DCC : seul habilité à la galerie et au direct.
     */
    public function managesMedia(): bool
    {
        return $this->isAdmin()
            || ($this->isResponsable() && in_array($this->dept, ['Médias/DCC', 'Médias', 'DCC'], true));
    }

    /**
     * Membre du répertoire correspondant au compte (rattachement par email).
     */
    public function member(): ?Member
    {
        return Member::where('email', $this->email)->first();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetRequested($token));
    }

    public function isSocialResponsable(): bool
    {
        return $this->isResponsable() && $this->dept === 'Social';
    }
}
