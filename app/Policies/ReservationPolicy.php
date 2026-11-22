<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/** Un voyageur ne voit et ne modifie que ses propres réservations. */
class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function abandonner(User $user, Reservation $reservation): Response
    {
        if ($reservation->user_id !== $user->id) {
            return Response::deny();
        }

        return $reservation->statut === Reservation::STATUT_EN_ATTENTE
            ? Response::allow()
            : Response::deny('Seule une réservation en attente peut être abandonnée.');
    }
}