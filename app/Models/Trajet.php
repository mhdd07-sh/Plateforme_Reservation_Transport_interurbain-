<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trajet extends Model
{
    use HasFactory;

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_TERMINE = 'termine';

    protected $fillable = [
        'transporteur_id',
        'vehicule_id',
        'ville_depart',
        'ville_arrivee',
        'date_depart',
        'heure_depart',
        'prix_unitaire',
        'statut',
    ];

    protected $casts = [
        'date_depart' => 'date',
        'prix_unitaire' => 'integer',
    ];

    // --- Relations -------------------------------------------------------

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class);
    }

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Sièges actuellement retenus sur ce trajet (via reservation_siege). */
    public function siegesRetenus(): BelongsToMany
    {
        return $this->belongsToMany(Siege::class, 'reservation_siege', 'trajet_id', 'siege_id');
    }

    // --- Accesseurs ------------------------------------------------------

    /** Date + heure de départ en un seul objet Carbon : $trajet->depart_le */
    protected function departLe(): Attribute
    {
        return Attribute::get(
            fn () => Carbon::parse($this->date_depart->toDateString() . ' ' . $this->heure_depart)
        );
    }

    // --- Scopes ----------------------------------------------------------

    public function scopePublies(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PUBLIE);
    }

    /** Trajets dont le départ est dans le futur. */
    public function scopeAVenir(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereDate('date_depart', '>', today())
                ->orWhere(function (Builder $q2) {
                    $q2->whereDate('date_depart', today())
                        ->whereTime('heure_depart', '>', now()->format('H:i:s'));
                });
        });
    }

    /** Filtres de recherche : chaque paramètre vide est ignoré. */
    public function scopeRecherche(Builder $query, ?string $depart = null, ?string $arrivee = null, ?string $date = null): Builder
    {
        return $query
            ->when($depart, fn ($q) => $q->where('ville_depart', 'like', '%' . $depart . '%'))
            ->when($arrivee, fn ($q) => $q->where('ville_arrivee', 'like', '%' . $arrivee . '%'))
            ->when($date, fn ($q) => $q->whereDate('date_depart', Carbon::parse($date)->toDateString()));
    }
    /** Trajets visibles et réservables : publiés, à venir, d'un transporteur validé. */
    public function scopeVisibles(Builder $query): Builder
    {
        return $query->publies()
            ->aVenir()
            ->whereHas('transporteur', fn ($q) => $q->where('statut', Transporteur::STATUT_VALIDE));
    }

    /** Un voyageur peut-il encore réserver sur ce trajet ? */
    public function estReservable(): bool
    {
        return $this->estPublie()
            && $this->depart_le->isFuture()
            && $this->transporteur->estValide();
    }

    // --- Places et sièges ------------------------------------------------

    /** Nombre de places retenues (réservations en attente ou confirmées). */
    public function placesOccupees(): int
    {
        return $this->siegesRetenus()->count();
    }

    /**
     * Places encore libres. Pour une liste de trajets, préférer
     * Trajet::with('vehicule')->withCount('siegesRetenus') puis
     * $trajet->vehicule->capacite - $trajet->sieges_retenus_count (évite N requêtes).
     */
    public function placesDisponibles(): int
    {
        return max(0, $this->vehicule->capacite - $this->placesOccupees());
    }

    /** Requête des sièges libres du véhicule pour CE trajet (ordonnés par numéro). */
    public function siegesLibres(): Builder
    {
        return Siege::query()
            ->where('vehicule_id', $this->vehicule_id)
            ->whereNotIn('id', function ($sub) {
                $sub->select('siege_id')
                    ->from('reservation_siege')
                    ->where('trajet_id', $this->id);
            })
            ->orderBy('numero');
    }

    // --- Cycle de vie ----------------------------------------------------

    public function publier(): void
    {
        $this->update(['statut' => self::STATUT_PUBLIE]);
    }

    public function estPublie(): bool
    {
        return $this->statut === self::STATUT_PUBLIE;
    }

        /** Statut affiché : un trajet publié dont le départ est passé est « terminé ». */
    public function statutAffiche(): string
    {
        if ($this->statut === self::STATUT_PUBLIE && $this->depart_le->isPast()) {
            return self::STATUT_TERMINE;
        }

        return $this->statut;
    }

    public function libelleStatut(): string
    {
        return match ($this->statutAffiche()) {
            self::STATUT_BROUILLON => 'Brouillon',
            self::STATUT_PUBLIE => 'Publié',
            self::STATUT_ANNULE => 'Annulé',
            self::STATUT_TERMINE => 'Terminé',
            default => $this->statut,
        };
    }

    /** Couleur du badge (voir x-ui.badge). */
    public function toneStatut(): string
    {
        return match ($this->statutAffiche()) {
            self::STATUT_PUBLIE => 'success',
            self::STATUT_ANNULE => 'danger',
            self::STATUT_TERMINE => 'info',
            default => 'neutral',
        };
    }
}