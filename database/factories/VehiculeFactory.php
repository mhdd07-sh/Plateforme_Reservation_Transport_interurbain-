<?php

namespace Database\Factories;

use App\Models\Transporteur;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicule>
 */
class VehiculeFactory extends Factory
{
    /** Capacité réaliste par type de véhicule. */
    public const CAPACITES = [
        Vehicule::TYPE_MINIBUS => 18,
        Vehicule::TYPE_TATA => 28,
        Vehicule::TYPE_CAR => 45,
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(Vehicule::TYPES);

        return [
            'transporteur_id' => Transporteur::factory(),
            'immatriculation' => strtoupper(fake()->unique()->bothify('DK-####-??')),
            'marque' => fake()->randomElement(['Toyota', 'Mercedes', 'Hyundai', 'Iveco', 'Renault', 'Tata']),
            'type_vehicule' => $type,
            'capacite' => self::CAPACITES[$type],
        ];
    }

    /** Force le type et la capacité associée : Vehicule::factory()->deType('car'). */
    public function deType(string $type): static
    {
        return $this->state(fn () => [
            'type_vehicule' => $type,
            'capacite' => self::CAPACITES[$type],
        ]);
    }
}