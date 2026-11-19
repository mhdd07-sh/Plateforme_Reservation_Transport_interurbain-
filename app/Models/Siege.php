<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Un siège appartient à un véhicule. Sa disponibilité dépend du TRAJET :
 * voir Trajet::siegesLibres() et la table reservation_siege.
 */
class Siege extends Model
{
    protected $table = 'sieges';

    protected $fillable = [
        'vehicule_id',
        'numero',
    ];

    public function vehicule(): BelongsTo
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_siege')
            ->withPivot('trajet_id')
            ->withTimestamps();
    }
}