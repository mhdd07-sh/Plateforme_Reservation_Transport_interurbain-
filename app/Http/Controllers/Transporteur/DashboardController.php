<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $transporteur = $request->user()->transporteur;

        $stats = null;
        $prochains = collect();
        $dernieres = collect();

        if ($transporteur?->estValide()) {
            $vehicules = $transporteur->vehicules()->get();

            $trajets = $transporteur->trajets()
                ->with('vehicule')
                ->withCount('siegesRetenus')
                ->get();

            $confirmees = $transporteur->reservations()->where('reservations.statut', Reservation::STATUT_CONFIRMEE);
            $nombreConfirmees = (clone $confirmees)->count();
            $revenus = (int) (clone $confirmees)->sum('reservations.montant_total');

            $publies = $trajets->filter(fn (Trajet $t) => $t->statutAffiche() === Trajet::STATUT_PUBLIE);

            $stats = [
                'vehicules' => $vehicules->count(),
                'repartition' => $vehicules->countBy('type_vehicule')
                    ->map(fn (int $n, string $type) => $n . ' ' . mb_strtolower(Vehicule::libelleType($type)))
                    ->implode(', '),
                'actifs' => $vehicules->where('statut', Vehicule::STATUT_ACTIF)->count(),
                'trajets_publies' => $publies->count(),
                'departs_aujourdhui' => $publies->filter(fn (Trajet $t) => $t->date_depart->isToday())->count(),
                'confirmees' => $nombreConfirmees,
                'revenus' => $revenus,
                'moyenne' => $nombreConfirmees > 0 ? (int) round($revenus / $nombreConfirmees) : 0,
            ];

            $prochains = $trajets
                ->filter(fn (Trajet $t) => in_array($t->statut, [Trajet::STATUT_BROUILLON, Trajet::STATUT_PUBLIE], true)
                    && $t->depart_le->isFuture())
                ->sortBy(fn (Trajet $t) => $t->depart_le->timestamp)
                ->take(5)
                ->values();

            $dernieres = $transporteur->reservations()
                ->with(['user', 'sieges', 'trajet'])
                ->latest('reservations.created_at')
                ->limit(5)
                ->get();
        }

        return view('transporteur.dashboard', compact('transporteur', 'stats', 'prochains', 'dernieres'));
    }
}