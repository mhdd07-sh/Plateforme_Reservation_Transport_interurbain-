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
    public const STATUT_REFUSE = 'refuse';

      public function libelleStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_SUSPENDU => 'Suspendu',
            self::STATUT_REFUSE => 'Refusé',
            default => (string) $this->statut,
        };
    }

    /** Couleur du badge (voir x-ui.badge). */
    public function toneStatut(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_VALIDE => 'success',
            self::STATUT_SUSPENDU => 'danger',
            default => 'neutral',
        };
    }

    public function libelleType(): string
    {
        return $this->type === self::TYPE_PARTICULIER ? 'Particulier' : 'Compagnie';
    }

    /** Nom commercial, ou nom du responsable pour un particulier (charger la relation user). */
    public function nomAffiche(): string
    {
        return $this->nom_compagnie ?: ($this->user?->name ?? 'Transporteur');
    }
    
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