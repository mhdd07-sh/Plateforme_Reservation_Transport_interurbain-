<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RechercheTrajetController extends Controller
{
    /** Recherche publique : seuls les trajets visibles (publiés, à venir, transporteur validé). */
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'depart' => ['nullable', 'string', 'max:80'],
            'arrivee' => ['nullable', 'string', 'max:80'],
            'date' => ['nullable', 'date'],
            'prix_max' => ['nullable', 'integer', 'min:0'],
            'tri' => ['nullable', Rule::in(['heure', 'prix'])],
        ]);

        $trajets = Trajet::visibles()
            ->with(['vehicule', 'transporteur'])
            ->withCount('siegesRetenus')
            ->recherche($filtres['depart'] ?? null, $filtres['arrivee'] ?? null, $filtres['date'] ?? null)
            ->when($filtres['prix_max'] ?? null, fn ($q, $max) => $q->where('prix_unitaire', '<=', $max))
            ->when(
                ($filtres['tri'] ?? 'heure') === 'prix',
                fn ($q) => $q->orderBy('prix_unitaire')->orderBy('date_depart')->orderBy('heure_depart'),
                fn ($q) => $q->orderBy('date_depart')->orderBy('heure_depart'),
            )
            ->paginate(10)
            ->withQueryString();

        return view('trajets.index', compact('trajets', 'filtres'));
    }

    /** Détails d'un trajet et plan des sièges. */
    public function show(Trajet $trajet): View
    {
        $trajet->load(['vehicule.sieges', 'transporteur']);

        abort_unless($trajet->estReservable(), 404);

        // Un visiteur non connecté revient sur cette page après sa connexion.
        if (! auth()->check()) {
            redirect()->setIntendedUrl(route('trajets.show', $trajet));
        }

        $occupes = $trajet->siegesRetenus()->pluck('sieges.id')->all();

        return view('trajets.show', compact('trajet', 'occupes'));
    }
}