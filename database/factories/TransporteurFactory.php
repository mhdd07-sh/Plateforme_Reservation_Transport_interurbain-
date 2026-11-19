<?php

namespace Database\Factories;

use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transporteur>
 */
class TransporteurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->transporteur(),
            'nom_compagnie' => fake()->company(),
            'numero_agrement' => 'AGR-' . fake()->unique()->numerify('######'),
            'statut' => Transporteur::STATUT_VALIDE,
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['statut' => Transporteur::STATUT_EN_ATTENTE]);
    }
}