<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicule extends Model
{
    use HasFactory;

    public const TYPE_TATA = 'tata';
    public const TYPE_MINIBUS = 'minibus';
    public const TYPE_CAR = 'car';

    public const TYPES = [self::TYPE_TATA, self::TYPE_MINIBUS, self::TYPE_CAR];

    protected $fillable = [
        'transporteur_id',
        'immatriculation',
        'marque',
        'capacite',
        'type_vehicule',
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
}