<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transporteur\TrajetRequest;
use App\Models\Reservation;
use App\Models\Trajet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TrajetController extends Controller
{
    /** Liste filtrable par statut affiché (tous, publie, brouillon, termine, annule). */
    public function index(Request $request): View
    {
        $filtre = (string) $request->query('statut', 'tous');

        $trajets = $request->user()->transporteur->trajets()
            ->with('vehicule')
            ->withCount('siegesRetenus')
            ->limit(300)
            ->get()
            // Départs à venir d'abord (du plus proche au plus lointain), puis les passés (du plus récent au plus ancien).
            ->sortBy(fn (Trajet $t) => $t->depart_le->isPast()
                ? [1, -$t->depart_le->timestamp]
                : [0, $t->depart_le->timestamp])
            ->values();

        $compteurs = ['tous' => $trajets->count()];

        foreach ([Trajet::STATUT_PUBLIE, Trajet::STATUT_BROUILLON, Trajet::STATUT_TERMINE, Trajet::STATUT_ANNULE] as $statut) {
            $compteurs[$statut] = $trajets->filter(fn (Trajet $t) => $t->statutAffiche() === $statut)->count();
        }

        if ($filtre !== 'tous') {
            $trajets = $trajets->filter(fn (Trajet $t) => $t->statutAffiche() === $filtre)->values();
        }

        // Pagination manuelle (le filtre porte sur le statut affiché, calculé en PHP).
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagine = new LengthAwarePaginator(
            $trajets->forPage($page, 10)->values(),
            $trajets->count(),
            10,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('transporteur.trajets.index', ['trajets' => $pagine, 'filtre' => $filtre, 'compteurs' => $compteurs]);
    }

    /** Détail d'un trajet : remplissage et liste des passagers. */
    public function show(Trajet $trajet): View
    {
        Gate::authorize('view', $trajet);

        $trajet->load(['vehicule', 'transporteur']);

        $reservations = $trajet->reservations()
            ->with(['user', 'sieges'])
            ->latest()
            ->get();

        $occupes = $trajet->placesOccupees();

        $revenus = (int) $reservations->where('statut', Reservation::STATUT_CONFIRMEE)->sum('montant_total');

        return view('transporteur.trajets.show', compact('trajet', 'reservations', 'occupes', 'revenus'));
    }

    public function create(Request $request): View
    {
        return view('transporteur.trajets.create', [
            'trajet' => new Trajet(),
            'vehicules' => $request->user()->transporteur->vehicules()->orderBy('immatriculation')->get(),
        ]);
    }

    public function store(TrajetRequest $request): RedirectResponse
    {
        $trajet = $request->user()->transporteur->trajets()->create([
            ...$request->validated(),
            'statut' => Trajet::STATUT_BROUILLON,
        ]);

        if ($request->input('action') === 'publier') {
            $trajet->publier();
            $message = 'Trajet créé et publié : il est maintenant visible des voyageurs.';
        } else {
            $message = 'Trajet enregistré en brouillon.';
        }

        return redirect()->route('transporteur.trajets.index')->with('succes', $message);
    }

    public function edit(Request $request, Trajet $trajet): View
    {
        Gate::authorize('update', $trajet);

        return view('transporteur.trajets.edit', [
            'trajet' => $trajet,
            'vehicules' => $request->user()->transporteur->vehicules()->orderBy('immatriculation')->get(),
        ]);
    }

    public function update(TrajetRequest $request, Trajet $trajet): RedirectResponse
    {
        Gate::authorize('update', $trajet);

        $trajet->update($request->validated());

        if ($request->input('action') === 'publier' && $trajet->statut === Trajet::STATUT_BROUILLON) {
            $trajet->publier();
            $message = 'Trajet modifié et publié.';
        } else {
            $message = 'Trajet modifié.';
        }

        return redirect()->route('transporteur.trajets.index')->with('succes', $message);
    }

    public function publier(Trajet $trajet): RedirectResponse
    {
        Gate::authorize('publier', $trajet);

        $trajet->publier();

        return back()->with('succes', 'Trajet publié : il est maintenant visible des voyageurs.');
    }

    public function annuler(Trajet $trajet): RedirectResponse
    {
        Gate::authorize('annuler', $trajet);

        $trajet->update(['statut' => Trajet::STATUT_ANNULE]);

        return back()->with('succes', 'Trajet annulé.');
    }

    public function destroy(Trajet $trajet): RedirectResponse
    {
        Gate::authorize('delete', $trajet);

        $trajet->delete();

        return redirect()->route('transporteur.trajets.index')->with('succes', 'Trajet supprimé.');
    }
}