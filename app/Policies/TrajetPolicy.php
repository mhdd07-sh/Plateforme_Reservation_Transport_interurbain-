<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Règles d'accès aux trajets d'un transporteur.
 * Un trajet qui a des réservations actives (en attente ou confirmées) ne peut plus être
 * modifié ni annulé ici : l'annulation avec remboursement sera traitée avec le paiement.
 */
class TrajetPolicy
{
    private function proprietaire(User $user, Trajet $trajet): bool
    {
        return $user->transporteur !== null
            && $trajet->transporteur_id === $user->transporteur->id;
    }

    private function aReservationsActives(Trajet $trajet): bool
    {
        return $trajet->reservations()
            ->whereIn('statut', [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_CONFIRMEE])
            ->exists();
    }

    public function update(User $user, Trajet $trajet): Response
    {
        if (! $this->proprietaire($user, $trajet)) {
            return Response::deny();
        }

        if (! in_array($trajet->statut, [Trajet::STATUT_BROUILLON, Trajet::STATUT_PUBLIE], true)) {
            return Response::deny('Ce trajet ne peut plus être modifié.');
        }

        return $this->aReservationsActives($trajet)
            ? Response::deny('Ce trajet a des réservations : modification impossible.')
            : Response::allow();
    }

    public function publier(User $user, Trajet $trajet): Response
    {
        if (! $this->proprietaire($user, $trajet)) {
            return Response::deny();
        }

        return $trajet->statut === Trajet::STATUT_BROUILLON
            ? Response::allow()
            : Response::deny('Seul un trajet en brouillon peut être publié.');
    }

    public function annuler(User $user, Trajet $trajet): Response
    {
        if (! $this->proprietaire($user, $trajet)) {
            return Response::deny();
        }

        if ($trajet->statut !== Trajet::STATUT_PUBLIE) {
            return Response::deny('Seul un trajet publié peut être annulé.');
        }

        return $this->aReservationsActives($trajet)
            ? Response::deny('Ce trajet a des réservations : annulation impossible pour le moment.')
            : Response::allow();
    }

    public function delete(User $user, Trajet $trajet): Response
    {
        if (! $this->proprietaire($user, $trajet)) {
            return Response::deny();
        }

        if ($trajet->statut !== Trajet::STATUT_BROUILLON) {
            return Response::deny('Seul un trajet en brouillon peut être supprimé.');
        }

        return $this->aReservationsActives($trajet)
            ? Response::deny('Ce trajet a des réservations : suppression impossible.')
            : Response::allow();
    }
}