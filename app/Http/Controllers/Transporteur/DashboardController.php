<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Trajet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $transporteur = $request->user()->transporteur;

        $stats = null;

        if ($transporteur?->estValide()) {
            $stats = [
                'vehicules' => $transporteur->vehicules()->count(),
                'trajets_publies' => $transporteur->trajets()->where('statut', Trajet::STATUT_PUBLIE)->count(),
                'reservations_confirmees' => $transporteur->reservations()
                    ->where('reservations.statut', Reservation::STATUT_CONFIRMEE)
                    ->count(),
            ];
        }

        return view('transporteur.dashboard', compact('transporteur', 'stats'));
    }
}