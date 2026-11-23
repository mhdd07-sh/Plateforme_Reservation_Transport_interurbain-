<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicule extends Model
{
    use HasFactory;

    public const TYPE_TATA = 'tata';
    public const TYPE_MINIBUS = 'minibus';
    public const TYPE_CAR = 'car';
    public const STATUT_ACTIF = 'actif';
    public const STATUT_MAINTENANCE = 'en_maintenance';

    public const TYPES = [self::TYPE_TATA, self::TYPE_MINIBUS, self::TYPE_CAR];

    protected $fillable = [
        'transporteur_id',
        'immatriculation',
        'marque',
        'capacite',
        'type_vehicule',
        'statut',
        'note_maintenance',
    ];

    protected $casts = [
        'capacite' => 'integer',
    ];

    protected static function booted(): void
    {
        // Les sièges numérotés 1..capacité sont créés automatiquement.
        static::created(function (Vehicule $vehicule) {
            $vehicule->genererSieges();
        });
    }

    public function transporteur(): BelongsTo
    {
        return $this->belongsTo(Transporteur::class);
    }

    public function sieges(): HasMany
    {
        return $this->hasMany(Siege::class)->orderBy('numero');
    }

    public function trajets(): HasMany
    {
        return $this->hasMany(Trajet::class);
    }

    /**
     * Crée les sièges manquants (1..capacité). Idempotent : peut être rappelée
     * après une modification de la capacité (n'ajoute que ce qui manque).
     */
    public function genererSieges(): void
    {
        if ($this->capacite < 1) {
            return;
        }

        $existants = $this->sieges()->pluck('numero')->all();
        $manquants = array_values(array_diff(range(1, $this->capacite), $existants));

        if ($manquants !== []) {
            $this->sieges()->createMany(array_map(fn (int $n) => ['numero' => $n], $manquants));
        }
    }

        /**
     * Adapte les sièges à la capacité (ajoute les manquants, supprime le surplus).
     * Retourne false, sans rien supprimer, si un siège en surplus a déjà été réservé.
     */
    public function synchroniserSieges(): bool
    {
        $surplus = Siege::where('vehicule_id', $this->id)->where('numero', '>', $this->capacite);

        $dejaUtilises = (clone $surplus)
            ->whereIn('id', fn ($q) => $q->select('siege_id')->from('reservation_siege'))
            ->exists();

        if ($dejaUtilises) {
            return false;
        }

        $surplus->delete();
        $this->genererSieges();

        return true;
    }

    /**
     * Règle de disponibilité (méthode vérifierDisponibilité() du diagramme de classes) :
     * le véhicule n'est pas déjà affecté à un autre trajet non annulé à la même date et heure.
     * $ignorerTrajetId sert lors de la modification d'un trajet existant.
     */
    public function estDisponible(string $date, string $heure, ?int $ignorerTrajetId = null): bool
    {
        return ! $this->trajets()
            ->where('date_depart', Carbon::parse($date)->toDateString())
            ->where('heure_depart', Carbon::parse($heure)->format('H:i:s'))
            ->where('statut', '!=', Trajet::STATUT_ANNULE)
            ->when($ignorerTrajetId, fn ($q) => $q->where('id', '!=', $ignorerTrajetId))
            ->exists();
    }

        /** Libellé affiché pour un type de véhicule. */
    public static function libelleType(?string $type): string
    {
        return match ($type) {
            self::TYPE_MINIBUS => 'Minibus',
            self::TYPE_TATA => 'Bus Tata',
            self::TYPE_CAR => 'Grand car',
            default => (string) $type,
        };
    }

    public function estActif(): bool
    {
        return $this->statut === self::STATUT_ACTIF;
    }

    /** Prochain départ (non annulé) de ce véhicule. */
    public function prochainTrajet(): HasOne
    {
        return $this->hasOne(Trajet::class)
            ->where('statut', '!=', Trajet::STATUT_ANNULE)
            ->aVenir()
            ->orderBy('date_depart')
            ->orderBy('heure_depart');
    }
}