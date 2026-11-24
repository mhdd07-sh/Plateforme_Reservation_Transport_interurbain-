<?php

namespace App\Http\Controllers\Voyageur;

use App\Exceptions\ReservationImpossibleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Voyageur\ReservationRequest;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Services\ReservationService;
use App\Support\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Exceptions\AnnulationImpossibleException;
use App\Services\AnnulationService;

class ReservationController extends Controller
{
    /** Historique : filtre par statut affiché (toutes, en_attente, confirmee, terminee, annulee). */
    public function index(Request $request): View
    {
        $filtre = (string) $request->query('statut', 'toutes');

        $reservations = $request->user()->reservations()
            ->with(['trajet.vehicule', 'trajet.transporteur', 'sieges'])
            ->latest()
            ->limit(200)
            ->get();

        $compteurs = [
            'toutes' => $reservations->count(),
            'en_attente' => $reservations->filter(fn (Reservation $r) => $r->statutAffiche() === 'en_attente')->count(),
            'confirmee' => $reservations->filter(fn (Reservation $r) => $r->statutAffiche() === 'confirmee')->count(),
            'terminee' => $reservations->filter(fn (Reservation $r) => $r->statutAffiche() === 'terminee')->count(),
            'annulee' => $reservations->filter(fn (Reservation $r) => in_array($r->statutAffiche(), ['annulee', 'expiree'], true))->count(),
        ];

        if ($filtre !== 'toutes') {
            $reservations = $reservations->filter(function (Reservation $r) use ($filtre) {
                return $filtre === 'annulee'
                    ? in_array($r->statutAffiche(), ['annulee', 'expiree'], true)
                    : $r->statutAffiche() === $filtre;
            });
        }

        return view('voyageur.reservations.index', compact('reservations', 'filtre', 'compteurs'));
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);

        // Une retenue dont le délai est dépassé est libérée dès qu'on la consulte.
        if ($reservation->statut === Reservation::STATUT_EN_ATTENTE && $reservation->expire_at?->isPast()) {
            $reservation->expirer();
        }

        $reservation->load(['trajet.vehicule', 'trajet.transporteur', 'sieges', 'paiement', 'user']);

        $qr = $reservation->statut === Reservation::STATUT_CONFIRMEE
            ? QrCode::svg($reservation->reference, 200)
            : null;

        return view('voyageur.reservations.show', compact('reservation', 'qr'));
    }

    public function store(ReservationRequest $request, Trajet $trajet, ReservationService $service): RedirectResponse
    {
        try {
            $reservation = $service->reserver($request->user(), $trajet, $request->validated('sieges'));
        } catch (ReservationImpossibleException $e) {
            return redirect()->route('trajets.show', $trajet)->with('erreur', $e->getMessage());
        }

        return redirect()
            ->route('voyageur.paiement.create', $reservation)
            ->with('succes', 'Vos sièges sont retenus pendant ' . Reservation::DELAI_EXPIRATION_MINUTES . ' minutes.');
    }

    /** Abandonne une réservation en attente et libère ses sièges. */
    public function abandonner(Reservation $reservation): RedirectResponse
    {
        Gate::authorize('abandonner', $reservation);

        $reservation->annuler();

        return redirect()->route('voyageur.reservations.index')->with('succes', 'Réservation abandonnée, sièges libérés.');
    }

        /** Annule une réservation confirmée et rembourse le voyageur. */
    public function annuler(Reservation $reservation, AnnulationService $annulations): RedirectResponse
    {
        Gate::authorize('annuler', $reservation);

        try {
            $annulations->annulerReservation($reservation);
        } catch (AnnulationImpossibleException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()
            ->route('voyageur.reservations.show', $reservation)
            ->with('succes', 'Réservation annulée : ' . number_format($reservation->montant_total, 0, ',', ' ') . ' FCFA vous sont remboursés.');
    }
}