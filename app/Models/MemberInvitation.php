<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'email',
        'token_hash',
        'status',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public static function createFor(Member $member, string $email, ?int $createdBy = null, ?\DateTimeInterface $expiresAt = null): self
    {
        $token = Str::random(40);

        $invitation = static::create([
            'member_id' => $member->id,
            'email' => strtolower(trim($email)),
            'token_hash' => Hash::make($token),
            'status' => 'pending',
            'expires_at' => $expiresAt ?? now()->addDays(7),
            'created_by' => $createdBy,
        ]);

        $invitation->token = $token;

        return $invitation;
    }

    public function isValid(string $token): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if (filled($this->revoked_at)) {
            return false;
        }

        if (filled($this->expires_at) && $this->expires_at->isPast()) {
            return false;
        }

        return Hash::check($token, $this->token_hash);
    }

    public function accept(string $token, array $userData): ?User
    {
        if (! $this->isValid($token)) {
            return null;
        }

        $email = strtolower(trim((string) ($userData['email'] ?? $this->email)));
        $username = (string) ($userData['username'] ?? $this->member?->name ?? '');
        $password = (string) ($userData['password'] ?? '');

        if ($email === '' || $username === '' || $password === '') {
            return null;
        }

        $user = User::query()->firstOrCreate([
            'email' => $email,
        ], [
            'username' => $username,
            'full_name' => (string) ($userData['full_name'] ?? $this->member?->name ?? $username),
            'phone' => (string) ($userData['phone'] ?? $this->member?->phone ?? ''),
            'member_id' => $this->member_id,
            'password' => Hash::make($password),
            'status' => 'pending',
            'role' => 'user',
            'created_by' => 'invitation',
        ]);

        $user->member_id = $this->member_id;
        $user->save();

        $this->status = 'used';
        $this->used_at = now();
        $this->save();

        return $user;
    }
}
