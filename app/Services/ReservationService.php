<?php

namespace App\Services;

use App\Exceptions\ReservationImpossibleException;
use App\Models\Reservation;
use App\Models\Siege;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public const MAX_SIEGES_PAR_RESERVATION = 5;

    public const MAX_RESERVATIONS_EN_ATTENTE = 3;

    /**
     * Retient des sièges pour un voyageur et crée la réservation « en_attente ».
     *
     * Protection contre la double réservation, en trois niveaux :
     *  1. verrou (SELECT ... FOR UPDATE) sur la ligne du trajet : les réservations d'un même
     *     trajet sont traitées l'une après l'autre ;
     *  2. vérification que les sièges ne sont pas déjà retenus ;
     *  3. contrainte UNIQUE (trajet_id, siege_id) en base, en dernier recours.
     *
     * @param  int[]  $siegeIds
     * @throws ReservationImpossibleException
     */
    public function reserver(User $voyageur, Trajet $trajet, array $siegeIds): Reservation
    {
        $siegeIds = array_values(array_unique(array_map('intval', $siegeIds)));

        if (! $voyageur->estVoyageur()) {
            throw new ReservationImpossibleException('Seuls les voyageurs peuvent réserver.');
        }

        if ($siegeIds === [] || count($siegeIds) > self::MAX_SIEGES_PAR_RESERVATION) {
            throw new ReservationImpossibleException(
                'Choisissez entre 1 et ' . self::MAX_SIEGES_PAR_RESERVATION . ' sièges.'
            );
        }

        try {
            return DB::transaction(function () use ($voyageur, $trajet, $siegeIds) {
                // 1. Verrou sur le trajet.
                $trajet = Trajet::query()->with('transporteur')->lockForUpdate()->findOrFail($trajet->id);

                if (! $trajet->estReservable()) {
                    throw new ReservationImpossibleException("Ce trajet n'est plus disponible à la réservation.");
                }

                // Libère les retenues expirées de ce trajet (sans dépendre du planificateur).
                Reservation::enAttenteExpirees()->where('trajet_id', $trajet->id)->get()->each->expirer();

                // Les sièges doivent appartenir au véhicule du trajet.
                $valides = Siege::where('vehicule_id', $trajet->vehicule_id)->whereIn('id', $siegeIds)->count();

                if ($valides !== count($siegeIds)) {
                    throw new ReservationImpossibleException('Un des sièges choisis est invalide.');
                }

                // 2. Aucun siège ne doit déjà être retenu.
                $dejaPris = DB::table('reservation_siege')
                    ->where('trajet_id', $trajet->id)
                    ->whereIn('siege_id', $siegeIds)
                    ->exists();

                if ($dejaPris) {
                    throw new ReservationImpossibleException(
                        "Désolé, un des sièges choisis vient d'être réservé. Veuillez en choisir un autre."
                    );
                }

                // Limite anti-accaparement : pas plus de N retenues non payées à la fois.
                $enAttente = Reservation::where('user_id', $voyageur->id)
                    ->where('statut', Reservation::STATUT_EN_ATTENTE)
                    ->where('expire_at', '>', now())
                    ->count();

                if ($enAttente >= self::MAX_RESERVATIONS_EN_ATTENTE) {
                    throw new ReservationImpossibleException(
                        'Vous avez déjà ' . self::MAX_RESERVATIONS_EN_ATTENTE
                        . ' réservations en attente de paiement. Payez-les ou abandonnez-en une.'
                    );
                }

                $reservation = Reservation::create([
                    'user_id' => $voyageur->id,
                    'trajet_id' => $trajet->id,
                    'nombre_places' => count($siegeIds),
                    'montant_total' => count($siegeIds) * $trajet->prix_unitaire,
                ]);

                $reservation->attacherSieges($siegeIds);

                return $reservation;
            });
        } catch (UniqueConstraintViolationException) {
            // 3. Filet de sécurité : la contrainte unique a refusé un doublon.
            throw new ReservationImpossibleException(
                "Désolé, un des sièges choisis vient d'être réservé. Veuillez en choisir un autre."
            );
        }
    }
}