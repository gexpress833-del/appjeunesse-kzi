<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'type',
    'status',
    'description',
    'logo_url',
    'phone',
    'email',
    'address',
    'pastor_principal_id',
])]
class Church extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pastorPrincipal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pastor_principal_id');
    }
}
