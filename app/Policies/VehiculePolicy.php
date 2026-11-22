<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicule;

/**
 * Un transporteur ne peut gérer que ses propres véhicules.
 */
class VehiculePolicy
{
    private function proprietaire(User $user, Vehicule $vehicule): bool
    {
        return $user->transporteur !== null
            && $vehicule->transporteur_id === $user->transporteur->id;
    }

    public function update(User $user, Vehicule $vehicule): bool
    {
        return $this->proprietaire($user, $vehicule);
    }

    public function delete(User $user, Vehicule $vehicule): bool
    {
        return $this->proprietaire($user, $vehicule);
    }
}