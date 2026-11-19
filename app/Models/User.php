<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_VOYAGEUR = 'voyageur';
    public const ROLE_TRANSPORTEUR = 'transporteur';
    public const ROLE_ADMIN = 'admin';

    public const ROLES = [self::ROLE_VOYAGEUR, self::ROLE_TRANSPORTEUR, self::ROLE_ADMIN];

    /**
     * Volontairement SANS 'role' ni 'actif' : ils ne doivent jamais être
     * modifiables par un formulaire (mass assignment). On les affecte
     * explicitement : $user->role = User::ROLE_ADMIN; $user->save();
     */
    protected $fillable = [
        'name',
        'email',
        'telephone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    // --- Relations -------------------------------------------------------

    public function transporteur(): HasOne
    {
        return $this->hasOne(Transporteur::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // --- Aides sur le rôle ----------------------------------------------

    public function estVoyageur(): bool
    {
        return $this->role === self::ROLE_VOYAGEUR;
    }

    public function estTransporteur(): bool
    {
        return $this->role === self::ROLE_TRANSPORTEUR;
    }

    public function estAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function aRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}