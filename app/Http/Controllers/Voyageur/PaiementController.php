<?php

namespace App\Http\Controllers\Voyageur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voyageur\PaiementRequest;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaiementController extends Controller
{
    /** Étape « Paiement » : choix du moyen et du numéro. */
    public function create(Reservation $reservation): View|RedirectResponse
    {
        if ($retour = $this->verifier($reservation)) {
            return $retour;
        }

        $reservation->load(['trajet.vehicule', 'trajet.transporteur', 'sieges']);

        return view('voyageur.paiement.create', compact('reservation'));
    }

    /** Initie la transaction auprès de l'opérateur (simulé), puis demande le code de confirmation. */
    public function store(PaiementRequest $request, Reservation $reservation, PaiementServiceInterface $paiements): RedirectResponse
    {
        if ($retour = $this->verifier($reservation)) {
            return $retour;
        }

        $resultat = $paiements->initier($reservation, $request->validated('moyen'), $request->validated('numero'));

        if (! $resultat->succes) {
            return back()->withInput()->with('erreur', $resultat->message);
        }

        return redirect()
            ->route('voyageur.paiement.code', $resultat->paiement)
            ->with('succes', $resultat->message);
    }

    /** Saisie du code de confirmation reçu par le voyageur. */
    public function code(Paiement $paiement): View|RedirectResponse
    {
        $reservation = $paiement->reservation;

        if ($retour = $this->verifier($reservation)) {
            return $retour;
        }

        if ($paiement->statut_paiement !== Paiement::STATUT_INITIE) {
            return redirect()
                ->route('voyageur.paiement.create', $reservation)
                ->with('erreur', "Ce paiement n'est plus en cours. Veuillez recommencer.");
        }

        return view('voyageur.paiement.code', compact('paiement', 'reservation'));
    }

    /** Valide le code : succès => la réservation est confirmée. */
    public function confirmer(Request $request, Paiement $paiement, PaiementServiceInterface $paiements): RedirectResponse
    {
        $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Saisissez le code reçu.', 'code.digits' => 'Le code comporte 6 chiffres.']
        );

        $reservation = $paiement->reservation;

        if ($retour = $this->verifier($reservation)) {
            return $retour;
        }

        $resultat = $paiements->confirmer($paiement, (string) $request->input('code'));

        if (! $resultat->succes) {
            return redirect()
                ->route('voyageur.paiement.create', $reservation)
                ->with('erreur', $resultat->message . ' Veuillez relancer le paiement.');
        }

        DB::transaction(function () use ($reservation, $paiement) {
            $reservation->update(['operateur_paiement' => $paiement->moyen_paiement]);
            $reservation->confirmer();
        });

        return redirect()
            ->route('voyageur.reservations.show', $reservation)
            ->with('succes', 'Paiement effectué : votre réservation est confirmée.');
    }

    /**
     * Garde-fous communs : propriétaire, réservation encore en attente, délai non dépassé.
     * Retourne une redirection si le paiement n'est plus possible, sinon null.
     */
    private function verifier(Reservation $reservation): ?RedirectResponse
    {
        Gate::authorize('view', $reservation);

        if ($reservation->statut === Reservation::STATUT_CONFIRMEE) {
            return redirect()
                ->route('voyageur.reservations.show', $reservation)
                ->with('succes', 'Cette réservation est déjà payée.');
        }

        Gate::authorize('payer', $reservation);

        if ($reservation->expire_at?->isPast()) {
            $reservation->expirer();

            return redirect()
                ->route('voyageur.reservations.show', $reservation)
                ->with('erreur', 'Le délai de paiement est dépassé : les sièges ont été libérés.');
        }

        return null;
    }
}