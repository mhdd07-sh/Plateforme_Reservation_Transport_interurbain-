<?php

namespace App\Http\Controllers\Voyageur;

use App\Exceptions\ReservationImpossibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Voyageur\ReservationRequest;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = $request->user()->reservations()
            ->with('trajet')
            ->latest()
            ->paginate(10);

        return view('voyageur.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        // Une retenue dont le délai est dépassé est libérée dès qu'on la consulte.
        if ($reservation->statut === Reservation::STATUT_EN_ATTENTE && $reservation->expire_at?->isPast()) {
            $reservation->expirer();
        }

        $reservation->load(['trajet.vehicule', 'trajet.transporteur', 'sieges', 'paiement']);

        return view('voyageur.reservations.show', compact('reservation'));
    }

    public function store(ReservationRequest $request, Trajet $trajet, ReservationService $service): RedirectResponse
    {
        try {
            $reservation = $service->reserver($request->user(), $trajet, $request->validated('sieges'));
        } catch (ReservationImpossibleException $e) {
            return redirect()->route('trajets.show', $trajet)->with('erreur', $e->getMessage());
        }

        return redirect()
            ->route('voyageur.reservations.show', $reservation)
            ->with('succes', 'Vos sièges sont retenus pendant ' . Reservation::DELAI_EXPIRATION_MINUTES . ' minutes.');
    }

    /** Abandonne une réservation en attente et libère ses sièges. */
    public function abandonner(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('abandonner', $reservation);

        $reservation->annuler();

        return redirect()->route('voyageur.reservations.index')->with('succes', 'Réservation abandonnée, sièges libérés.');
    }
}