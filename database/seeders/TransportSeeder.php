<?php

namespace Database\Seeders;

use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\Vehicule;
use Illuminate\Database\Seeder;

/**
 * Véhicules (avec leurs sièges, générés automatiquement) et trajets de démonstration.
 */
class TransportSeeder extends Seeder
{
    public function run(): void
    {
        // [départ, arrivée, prix en FCFA]
        $lignes = [
            ['Dakar', 'Thiès', 1500],
            ['Dakar', 'Mbour', 2000],
            ['Dakar', 'Kaolack', 3500],
            ['Dakar', 'Saint-Louis', 4000],
            ['Dakar', 'Touba', 4500],
            ['Thiès', 'Saint-Louis', 3000],
            ['Mbour', 'Kaolack', 2000],
            ['Dakar', 'Ziguinchor', 8000],
        ];

        $heures = ['06:00:00', '08:30:00', '10:00:00', '14:00:00', '16:30:00'];
        $compteur = 0;

        Transporteur::where('statut', Transporteur::STATUT_VALIDE)->get()->each(
            function (Transporteur $transporteur) use ($lignes, $heures, &$compteur) {
                foreach ([Vehicule::TYPE_MINIBUS, Vehicule::TYPE_TATA, Vehicule::TYPE_CAR] as $type) {
                    $vehicule = Vehicule::factory()->deType($type)->create([
                        'transporteur_id' => $transporteur->id,
                    ]);

                    // 3 trajets par véhicule, sur 3 jours différents (jamais deux fois la même date)
                    foreach ([1, 2, 3] as $jour) {
                        [$depart, $arrivee, $prix] = $lignes[$compteur % count($lignes)];
                        $compteur++;

                        Trajet::create([
                            'transporteur_id' => $transporteur->id,
                            'vehicule_id' => $vehicule->id,
                            'ville_depart' => $depart,
                            'ville_arrivee' => $arrivee,
                            'date_depart' => today()->addDays($jour)->toDateString(),
                            'heure_depart' => $heures[$compteur % count($heures)],
                            'prix_unitaire' => $prix,
                            // un trajet sur cinq reste en brouillon (non visible des voyageurs)
                            'statut' => $compteur % 5 === 0 ? Trajet::STATUT_BROUILLON : Trajet::STATUT_PUBLIE,
                        ]);
                    }
                }
            }
        );
    }
}