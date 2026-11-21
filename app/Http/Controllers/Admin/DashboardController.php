<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'voyageurs' => User::where('role', User::ROLE_VOYAGEUR)->count(),
            'transporteurs' => Transporteur::where('statut', Transporteur::STATUT_VALIDE)->count(),
            'transporteurs_en_attente' => Transporteur::where('statut', Transporteur::STATUT_EN_ATTENTE)->count(),
            'trajets_publies' => Trajet::publies()->count(),
            'reservations_confirmees' => Reservation::where('statut', Reservation::STATUT_CONFIRMEE)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}