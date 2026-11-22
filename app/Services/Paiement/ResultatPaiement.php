<?php

namespace App\Services\Paiement;

use App\Models\Paiement;

/**
 * Résultat d'une opération de paiement : succès ou échec, message affichable, et le
 * paiement concerné (déjà mis à jour en base).
 */
final class ResultatPaiement
{
    public function __construct(
        public readonly bool $succes,
        public readonly string $message,
        public readonly Paiement $paiement,
    ) {
    }
}