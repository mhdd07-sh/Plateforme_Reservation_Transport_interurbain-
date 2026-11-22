<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Transporteur extends Model
{
    use HasFactory;

    public const TYPE_PARTICULIER = 'particulier';
    public const TYPE_COMPAGNIE = 'compagnie';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_SUSPENDU = 'suspendu';

    protected $fillable = [
        'user_id',
        'type',
        'nom_compagnie',
        'numero_agrement',
        'statut',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicules(): HasMany
    {
        return $this->hasMany(Vehicule::class);
    }

    public function trajets(): HasMany
    {
        return $this->hasMany(Trajet::class);
    }

    /** Toutes les réservations faites sur les trajets de ce transporteur. */
    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(Reservation::class, Trajet::class);
    }

    public function estValide(): bool
    {
        return $this->statut === self::STATUT_VALIDE;
    }
}