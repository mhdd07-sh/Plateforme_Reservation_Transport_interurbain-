<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transporteur\VehiculeRequest;
use App\Models\Vehicule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function index(Request $request): View
    {
        $transporteur = $request->user()->transporteur;

        $filtres = $request->validate([
            'type' => ['nullable', Rule::in(Vehicule::TYPES)],
            'statut' => ['nullable', Rule::in([Vehicule::STATUT_ACTIF, Vehicule::STATUT_MAINTENANCE])],
        ]);

        $flotte = $transporteur->vehicules()->get();

        $stats = [
            'total' => $flotte->count(),
            'actifs' => $flotte->where('statut', Vehicule::STATUT_ACTIF)->count(),
            'capacite' => (int) $flotte->sum('capacite'),
        ];

        $vehicules = $transporteur->vehicules()
            ->with('prochainTrajet')
            ->when($filtres['type'] ?? null, fn ($q, $type) => $q->where('type_vehicule', $type))
            ->when($filtres['statut'] ?? null, fn ($q, $statut) => $q->where('statut', $statut))
            ->orderBy('immatriculation')
            ->get();

        return view('transporteur.vehicules.index', compact('vehicules', 'stats'));
    }

    public function create(): View
    {
        return view('transporteur.vehicules.create', ['vehicule' => new Vehicule(['statut' => Vehicule::STATUT_ACTIF])]);
    }

    public function store(VehiculeRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        if ($donnees['statut'] === Vehicule::STATUT_ACTIF) {
            $donnees['note_maintenance'] = null;
        }

        // Les sièges 1..capacité sont générés automatiquement (événement created du modèle).
        $request->user()->transporteur->vehicules()->create($donnees);

        return redirect()
            ->route('transporteur.vehicules.index')
            ->with('succes', 'Véhicule ajouté avec ses sièges.');
    }

    public function edit(Vehicule $vehicule): View
    {
        Gate::authorize('update', $vehicule);

        return view('transporteur.vehicules.edit', compact('vehicule'));
    }

    public function update(VehiculeRequest $request, Vehicule $vehicule): RedirectResponse
    {
        Gate::authorize('update', $vehicule);

        $donnees = $request->validated();

        if ($donnees['statut'] === Vehicule::STATUT_ACTIF) {
            $donnees['note_maintenance'] = null;
        }

        DB::transaction(function () use ($donnees, $vehicule) {
            // Un véhicule ne peut pas partir en maintenance s'il a encore des départs programmés.
            if (
                $donnees['statut'] === Vehicule::STATUT_MAINTENANCE
                && $vehicule->statut !== Vehicule::STATUT_MAINTENANCE
                && $vehicule->prochainTrajet()->exists()
            ) {
                throw ValidationException::withMessages([
                    'statut' => 'Ce véhicule a des départs programmés : annulez-les ou changez de véhicule avant de le mettre en maintenance.',
                ]);
            }

            $vehicule->update($donnees);

            if (! $vehicule->synchroniserSieges()) {
                // L'exception annule la transaction (la capacité n'est pas modifiée).
                throw ValidationException::withMessages([
                    'capacite' => 'Impossible de réduire la capacité : des sièges à supprimer ont déjà été réservés.',
                ]);
            }
        });

        return redirect()
            ->route('transporteur.vehicules.index')
            ->with('succes', 'Véhicule modifié.');
    }

    public function destroy(Vehicule $vehicule): RedirectResponse
    {
        Gate::authorize('delete', $vehicule);

        if ($vehicule->trajets()->exists()) {
            return back()->with('erreur', 'Ce véhicule est affecté à des trajets : suppression impossible.');
        }

        $vehicule->delete();

        return redirect()
            ->route('transporteur.vehicules.index')
            ->with('succes', 'Véhicule supprimé.');
    }
}