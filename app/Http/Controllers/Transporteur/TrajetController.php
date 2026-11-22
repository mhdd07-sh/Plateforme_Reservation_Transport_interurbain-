<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transporteur\TrajetRequest;
use App\Models\Trajet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TrajetController extends Controller
{
    public function index(Request $request): View
    {
        $trajets = $request->user()->transporteur->trajets()
            ->with('vehicule')
            ->withCount('siegesRetenus')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->input('statut')))
            ->orderByDesc('date_depart')
            ->orderByDesc('heure_depart')
            ->paginate(10)
            ->withQueryString();

        return view('transporteur.trajets.index', compact('trajets'));
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