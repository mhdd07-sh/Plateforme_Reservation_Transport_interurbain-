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

    /** Détail d'un trajet et liste de ses passagers. */
    public function view(User $user, Trajet $trajet): bool
    {
        return $this->proprietaire($user, $trajet);
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

        /** Un trajet publié et à venir peut être annulé, même avec des réservations (elles sont remboursées). */
    public function annuler(User $user, Trajet $trajet): Response
    {
        if (! $this->proprietaire($user, $trajet)) {
            return Response::deny();
        }

        if ($trajet->statut !== Trajet::STATUT_PUBLIE) {
            return Response::deny('Seul un trajet publié peut être annulé.');
        }

        return $trajet->depart_le->isFuture()
            ? Response::allow()
            : Response::deny('Ce trajet est déjà parti : il ne peut plus être annulé.');
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