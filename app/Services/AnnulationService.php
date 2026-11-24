<?php

namespace App\Services;

use App\Exceptions\AnnulationImpossibleException;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Annulations avec remboursement (séquence 6.2 du rapport).
 * Tout se fait dans une transaction : si un remboursement échoue, rien n'est annulé.
 */
class AnnulationService
{
    public function __construct(private readonly PaiementServiceInterface $paiements)
    {
    }

    /**
     * Annulation, par le voyageur, d'une réservation confirmée.
     * Possible jusqu'à Reservation::DELAI_ANNULATION_HEURES avant le départ ; remboursement intégral.
     *
     * @throws AnnulationImpossibleException
     */
    public function annulerReservation(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            // Verrou : empêche deux annulations simultanées de la même réservation (double remboursement).
            $reservation = Reservation::with('trajet')->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->statut !== Reservation::STATUT_CONFIRMEE) {
                throw new AnnulationImpossibleException('Seule une réservation confirmée peut être annulée.');
            }

            if (! $reservation->peutEtreAnnulee()) {
                throw new AnnulationImpossibleException(
                    "L'annulation n'est plus possible : le départ est dans moins de " . Reservation::DELAI_ANNULATION_HEURES . ' heures.'
                );
            }

            $this->rembourser($reservation);
            $reservation->annuler('Annulation à la demande du voyageur');
        });
    }

    /**
     * Annulation d'un trajet publié (par le transporteur ou l'administrateur) :
     * les réservations confirmées sont remboursées, les retenues en attente sont libérées.
     *
     * @return array{remboursees: int, liberees: int}
     *
     * @throws AnnulationImpossibleException
     */
    public function annulerTrajet(Trajet $trajet, ?string $motif = null): array
    {
        return DB::transaction(function () use ($trajet, $motif) {
            $trajet = Trajet::lockForUpdate()->findOrFail($trajet->id);

            if ($trajet->statut !== Trajet::STATUT_PUBLIE) {
                throw new AnnulationImpossibleException('Seul un trajet publié peut être annulé.');
            }

            if ($trajet->depart_le->isPast()) {
                throw new AnnulationImpossibleException('Ce trajet est déjà parti : il ne peut plus être annulé.');
            }

            $raison = $motif ?: 'Trajet annulé par le transporteur';
            $bilan = ['remboursees' => 0, 'liberees' => 0];

            $reservations = $trajet->reservations()
                ->whereIn('statut', [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_CONFIRMEE])
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                if ($reservation->statut === Reservation::STATUT_CONFIRMEE) {
                    $this->rembourser($reservation);
                    $bilan['remboursees']++;
                } else {
                    $bilan['liberees']++;
                }

                $reservation->annuler($raison);
            }

            $trajet->update([
                'statut' => Trajet::STATUT_ANNULE,
                'motif_annulation' => $motif,
            ]);

            return $bilan;
        });
    }

    /** Rembourse le dernier paiement réussi de la réservation (s'il existe). */
    private function rembourser(Reservation $reservation): void
    {
        $paiement = $reservation->paiements()
            ->where('statut_paiement', Paiement::STATUT_REUSSI)
            ->latest('id')
            ->first();

        if (! $paiement) {
            return;
        }

        $resultat = $this->paiements->rembourser($paiement);

        if (! $resultat->succes) {
            throw new AnnulationImpossibleException('Le remboursement a échoué : ' . $resultat->message);
        }
    }
}