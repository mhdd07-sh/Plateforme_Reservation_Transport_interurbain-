<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    public const MOYEN_WAVE = 'wave';
    public const MOYEN_ORANGE_MONEY = 'orange_money';
    public const MOYEN_CARTE = 'carte';

    public const MOYENS = [self::MOYEN_WAVE, self::MOYEN_ORANGE_MONEY, self::MOYEN_CARTE];

    public const STATUT_INITIE = 'initie';
    public const STATUT_REUSSI = 'reussi';
    public const STATUT_ECHOUE = 'echoue';
    public const STATUT_REMBOURSE = 'rembourse';

    protected $fillable = [
        'reservation_id',
        'montant',
        'moyen_paiement',
        'numero_paiement',
        'statut_paiement',
        'reference_transaction',
        'date_paiement',
    ];

    protected $casts = [
        'montant' => 'integer',
        'date_paiement' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function estReussi(): bool
    {
        return $this->statut_paiement === self::STATUT_REUSSI;
    }
}
