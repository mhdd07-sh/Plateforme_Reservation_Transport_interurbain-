<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'types' => ['nullable', 'array'],
            'types.*' => [Rule::in(Vehicule::TYPES)],
        ]);

        $depart = $filtres['depart'] ?? null;
        $arrivee = $filtres['arrivee'] ?? null;

        // Bande des 7 prochains jours : prix le plus bas de la ligne chaque jour.
        $prixParJour = Trajet::visibles()
            ->recherche($depart, $arrivee, null)
            ->whereDate('date_depart', '<=', today()->addDays(6))
            ->toBase()
            ->select('date_depart', DB::raw('MIN(prix_unitaire) as prix_min'))
            ->groupBy('date_depart')
            ->get()
            ->pluck('prix_min', 'date_depart');

        $bande = collect(range(0, 6))->map(function (int $i) use ($prixParJour) {
            $jour = today()->addDays($i);

            return ['date' => $jour, 'prix' => $prixParJour[$jour->toDateString()] ?? null];
        });

        $trajets = Trajet::visibles()
            ->with(['vehicule', 'transporteur'])
            ->withCount('siegesRetenus')
            ->recherche($depart, $arrivee, $filtres['date'] ?? null)
            ->when($filtres['prix_max'] ?? null, fn ($q, $max) => $q->where('prix_unitaire', '<=', $max))
            ->when($filtres['types'] ?? [], fn ($q, $types) => $q->whereHas('vehicule', fn ($v) => $v->whereIn('type_vehicule', $types)))
            ->when(
                ($filtres['tri'] ?? 'heure') === 'prix',
                fn ($q) => $q->orderBy('prix_unitaire')->orderBy('date_depart')->orderBy('heure_depart'),
                fn ($q) => $q->orderBy('date_depart')->orderBy('heure_depart'),
            )
            ->paginate(10)
            ->withQueryString();

        return view('trajets.index', compact('trajets', 'bande'));
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