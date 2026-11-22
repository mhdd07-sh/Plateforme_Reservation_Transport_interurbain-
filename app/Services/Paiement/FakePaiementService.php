<?php

namespace App\Services\Paiement;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Simulation d'un opérateur de paiement. Règles (voir config/paiement.php) :
 *  - un numéro qui se termine par « 00 » simule un solde insuffisant ;
 *  - le code de confirmation 123456 réussit, tout autre code échoue.
 */
class FakePaiementService implements PaiementServiceInterface
{
    public function initier(Reservation $reservation, string $moyen, string $numero): ResultatPaiement
    {
        if (! in_array($moyen, Paiement::MOYENS, true)) {
            throw new InvalidArgumentException("Moyen de paiement inconnu : {$moyen}");
        }

        $chiffres = preg_replace('/\D/', '', $numero);

        $paiement = Paiement::create([
            'reservation_id' => $reservation->id,
            'montant' => $reservation->montant_total,
            'moyen_paiement' => $moyen,
            // Jamais de numéro de carte complet en base : on ne garde que les 4 derniers chiffres.
            'numero_paiement' => $moyen === Paiement::MOYEN_CARTE ? '**** ' . substr($chiffres, -4) : $chiffres,
            'statut_paiement' => Paiement::STATUT_INITIE,
            'reference_transaction' => 'TX-' . strtoupper(Str::random(10)),
        ]);

        // Vérification du solde (simulée).
        if (str_ends_with($chiffres, (string) config('paiement.fake.suffixe_solde_insuffisant', '00'))) {
            $paiement->update(['statut_paiement' => Paiement::STATUT_ECHOUE]);

            return new ResultatPaiement(false, 'Transaction échouée : solde insuffisant.', $paiement);
        }

        return new ResultatPaiement(true, 'Un code de confirmation vient de vous être envoyé.', $paiement);
    }

    public function confirmer(Paiement $paiement, string $code): ResultatPaiement
    {
        if ($paiement->statut_paiement !== Paiement::STATUT_INITIE) {
            return new ResultatPaiement(false, 'Ce paiement ne peut plus être confirmé.', $paiement);
        }

        if (trim($code) !== (string) config('paiement.fake.code_valide', '123456')) {
            $paiement->update(['statut_paiement' => Paiement::STATUT_ECHOUE]);

            return new ResultatPaiement(false, 'Code de confirmation invalide.', $paiement);
        }

        $paiement->update([
            'statut_paiement' => Paiement::STATUT_REUSSI,
            'date_paiement' => now(),
        ]);

        return new ResultatPaiement(true, 'Paiement effectué avec succès.', $paiement);
    }

    public function rembourser(Paiement $paiement): ResultatPaiement
    {
        if (! $paiement->estReussi()) {
            return new ResultatPaiement(false, 'Seul un paiement réussi peut être remboursé.', $paiement);
        }

        $paiement->update(['statut_paiement' => Paiement::STATUT_REMBOURSE]);

        return new ResultatPaiement(true, 'Remboursement effectué.', $paiement);
    }
}