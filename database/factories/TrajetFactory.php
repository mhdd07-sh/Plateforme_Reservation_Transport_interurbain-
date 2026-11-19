<?php

namespace Database\Factories;

use App\Models\Trajet;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trajet>
 */
class TrajetFactory extends Factory
{
    public function definition(): array
    {
        $villes = ['Dakar', 'Thiès', 'Mbour', 'Saint-Louis', 'Touba', 'Kaolack', 'Ziguinchor', 'Tambacounda'];
        [$depart, $arrivee] = fake()->randomElements($villes, 2);

        return [
            'vehicule_id' => Vehicule::factory(),
            // Le transporteur du trajet est celui du véhicule choisi.
            'transporteur_id' => fn (array $attributes) => Vehicule::find($attributes['vehicule_id'])->transporteur_id,
            'ville_depart' => $depart,
            'ville_arrivee' => $arrivee,
            'date_depart' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'heure_depart' => fake()->randomElement(['06:00:00', '08:30:00', '10:00:00', '14:00:00', '16:30:00']),
            'prix_unitaire' => fake()->randomElement([1500, 2000, 2500, 3500, 4000, 4500]),
            'statut' => Trajet::STATUT_PUBLIE,
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => Trajet::STATUT_BROUILLON]);
    }
}