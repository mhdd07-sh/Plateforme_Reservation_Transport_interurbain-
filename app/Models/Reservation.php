<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Reservation extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_CONFIRMEE = 'confirmee';
    public const STATUT_ANNULEE = 'annulee';
    public const STATUT_EXPIREE = 'expiree';

    /** Durée pendant laquelle les sièges sont retenus en attendant le paiement. */
    public const DELAI_EXPIRATION_MINUTES = 10;

    /** Annulation possible seulement si le départ est dans plus de N heures. */
    public const DELAI_ANNULATION_HEURES = 24;

    protected $fillable = [
        'reference',
        'user_id',
        'trajet_id',
        'nombre_places',
        'montant_total',
        'operateur_paiement',
        'statut',
        'expire_at',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'nombre_places' => 'integer',
        'montant_total' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            $reservation->statut ??= self::STATUT_EN_ATTENTE;

            if (empty($reservation->reference)) {
                do {
                    $reference = 'RES-' . strtoupper(Str::random(8));
                } while (static::where('reference', $reference)->exists());

                $reservation->reference = $reference;
            }

            if ($reservation->statut === self::STATUT_EN_ATTENTE && empty($reservation->expire_at)) {
                $reservation->expire_at = now()->addMinutes(self::DELAI_EXPIRATION_MINUTES);
            }
        });
    }

    // --- Relations -------------------------------------------------------

    /** Le voyageur qui a effectué la réservation. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trajet(): BelongsTo
    {
        return $this->belongsTo(Trajet::class);
    }

    public function sieges(): BelongsToMany
    {
        return $this->belongsToMany(Siege::class, 'reservation_siege')
            ->withPivot('trajet_id')
            ->withTimestamps();
    }

    /** Toutes les tentatives de paiement (un échec peut être suivi d'un nouvel essai). */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /** La tentative de paiement la plus récente. */
    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class)->latestOfMany();
    }

    // --- Scopes ----------------------------------------------------------

    public function scopeEnAttenteExpirees(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE)
            ->where('expire_at', '<=', now());
    }

    // --- Sièges ----------------------------------------------------------

    /**
     * Retient des sièges pour cette réservation (renseigne trajet_id dans le pivot).
     * Lève une QueryException si un siège est déjà pris sur ce trajet
     * (contrainte unique) : à intercepter dans le contrôleur.
     * À appeler dans une DB::transaction().
     */
    public function attacherSieges(array $siegeIds): void
    {
        $donnees = collect($siegeIds)
            ->mapWithKeys(fn ($id) => [$id => ['trajet_id' => $this->trajet_id]])
            ->all();

        $this->sieges()->attach($donnees);
    }

    public function libererSieges(): void
    {
        $this->sieges()->detach();
    }

    // --- Cycle de vie (méthodes du diagramme de classes) -----------------

    public function calculerMontant(): int
    {
        return $this->nombre_places * $this->trajet->prix_unitaire;
    }

    public function confirmer(): void
    {
        $this->update([
            'statut' => self::STATUT_CONFIRMEE,
            'expire_at' => null,
        ]);
    }

    public function annuler(): void
    {
        DB::transaction(function () {
            $this->libererSieges();
            $this->update(['statut' => self::STATUT_ANNULEE]);
        });
    }

    public function expirer(): void
    {
        DB::transaction(function () {
            $this->libererSieges();
            $this->update(['statut' => self::STATUT_EXPIREE]);
        });
    }

    public function estConfirmee(): bool
    {
        return $this->statut === self::STATUT_CONFIRMEE;
    }

    /** Annulable : confirmée et départ dans plus de DELAI_ANNULATION_HEURES heures. */
    public function peutEtreAnnulee(): bool
    {
        return $this->estConfirmee()
            && $this->trajet->depart_le->gt(now()->addHours(self::DELAI_ANNULATION_HEURES));
    }
}