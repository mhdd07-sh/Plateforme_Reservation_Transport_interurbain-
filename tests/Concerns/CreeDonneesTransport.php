<?php

namespace Tests\Concerns;

use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use App\Models\Vehicule;

trait CreeDonneesTransport
{
    /** Trajet publié, dans 3 jours, d'un transporteur validé, avec un véhicule neuf et ses sièges. */
    protected function creerTrajetPublie(int $capacite = 18, int $prix = 2000): Trajet
    {
        $transporteur = Transporteur::factory()->create();

        $vehicule = Vehicule::factory()->create([
            'transporteur_id' => $transporteur->id,
            'capacite' => $capacite,
        ]);

        return Trajet::factory()->create([
            'transporteur_id' => $transporteur->id,
            'vehicule_id' => $vehicule->id,
            'date_depart' => now()->addDays(3)->toDateString(),
            'heure_depart' => '08:30:00',
            'prix_unitaire' => $prix,
            'statut' => Trajet::STATUT_PUBLIE,
        ]);
    }

    /** Réservation sans sièges (suffisante pour tester le paiement). */
    protected function creerReservationSimple(Trajet $trajet, int $places = 1): Reservation
    {
        $voyageur = User::factory()->voyageur()->create();

        return Reservation::create([
            'user_id' => $voyageur->id,
            'trajet_id' => $trajet->id,
            'nombre_places' => $places,
            'montant_total' => $places * $trajet->prix_unitaire,
        ]);
    }
}