<?php

namespace App\Services\Paiement;

use App\Models\Paiement;
use App\Models\Reservation;

/**
 * Contrat du service de paiement (séquences 6.1 et 6.2 du rapport).
 * Le reste de l'application dépend de cette interface, jamais d'un opérateur précis :
 * on peut ainsi passer de la simulation à une vraie API (Wave, Orange Money) sans toucher
 * aux contrôleurs.
 */
interface PaiementServiceInterface
{
    /**
     * Initie la transaction : crée le Paiement (statut « initie »), vérifie le solde auprès
     * de l'opérateur et demande un code de confirmation au voyageur.
     * Échec (ex. solde insuffisant) : le Paiement passe à « echoue ».
     *
     * @param string $moyen  une valeur de Paiement::MOYENS (wave, orange_money, carte)
     * @param string $numero numéro de téléphone du compte mobile money (ou carte)
     */
    public function initier(Reservation $reservation, string $moyen, string $numero): ResultatPaiement;

    /**
     * Valide le paiement avec le code reçu par le voyageur.
     * Succès : « reussi » + date de paiement. Code invalide : « echoue ».
     * Ne modifie PAS la réservation : c'est le rôle du contrôleur (Reservation::confirmer()).
     */
    public function confirmer(Paiement $paiement, string $code): ResultatPaiement;

    /** Rembourse un paiement réussi (statut « rembourse »). */
    public function rembourser(Paiement $paiement): ResultatPaiement;
}