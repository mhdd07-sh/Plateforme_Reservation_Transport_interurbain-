<?php

namespace App\Http\Controllers\Voyageur;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Trajet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $reservations = $user->reservations()
            ->with(['trajet.vehicule', 'trajet.transporteur', 'sieges'])
            ->latest()
            ->limit(100)
            ->get();

        // Prochain départ confirmé.
        $prochain = $reservations
            ->filter(fn (Reservation $r) => $r->statut === Reservation::STATUT_CONFIRMEE && $r->trajet->depart_le->isFuture())
            ->sortBy(fn (Reservation $r) => $r->trajet->depart_le->timestamp)
            ->first();

        $stats = [
            'voyages' => $reservations->filter(fn (Reservation $r) => $r->statutAffiche() === 'terminee')->count(),
            'en_attente' => $reservations->filter(
                fn (Reservation $r) => $r->statut === Reservation::STATUT_EN_ATTENTE && $r->expire_at?->isFuture()
            )->count(),
            'a_venir' => $reservations->filter(fn (Reservation $r) => $r->statutAffiche() === 'confirmee')->count(),
            'total' => $reservations->where('statut', Reservation::STATUT_CONFIRMEE)->sum('montant_total'),
        ];

        $historique = $reservations->take(4);
        $dernierBillet = $reservations->firstWhere('statut', Reservation::STATUT_CONFIRMEE);

        $populaires = Trajet::visibles()
            ->select('ville_depart', 'ville_arrivee', DB::raw('MIN(prix_unitaire) as prix_min'), DB::raw('COUNT(*) as nombre'))
            ->groupBy('ville_depart', 'ville_arrivee')
            ->orderByDesc('nombre')
            ->limit(4)
            ->get();

        return view('voyageur.dashboard', compact('user', 'prochain', 'stats', 'historique', 'dernierBillet', 'populaires'));
    }
}