<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Transporteur;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Lignes les plus proposées, avec le prix le plus bas.
        $populaires = Trajet::visibles()
            ->select('ville_depart', 'ville_arrivee', DB::raw('MIN(prix_unitaire) as prix_min'), DB::raw('COUNT(*) as nombre'))
            ->groupBy('ville_depart', 'ville_arrivee')
            ->orderByDesc('nombre')
            ->orderBy('ville_depart')
            ->limit(4)
            ->get();

        // Chiffres réels de la plateforme (jamais de statistiques inventées).
        $stats = [
            'trajets' => Trajet::visibles()->count(),
            'compagnies' => Transporteur::where('statut', Transporteur::STATUT_VALIDE)->count(),
            'villes' => Trajet::visibles()->pluck('ville_depart')
                ->merge(Trajet::visibles()->pluck('ville_arrivee'))
                ->unique()
                ->count(),
        ];

        return view('accueil', compact('populaires', 'stats'));
    }
}