<?php

namespace App\Http\Controllers\Transporteur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transporteur\VehiculeRequest;
use App\Models\Vehicule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehiculeController extends Controller
{
    public function index(Request $request): View
    {
        $vehicules = $request->user()->transporteur->vehicules()
            ->withCount('trajets')
            ->latest()
            ->paginate(10);

        return view('transporteur.vehicules.index', compact('vehicules'));
    }

    public function create(): View
    {
        return view('transporteur.vehicules.create', ['vehicule' => new Vehicule()]);
    }

    public function store(VehiculeRequest $request): RedirectResponse
    {
        // Les sièges 1..capacité sont générés automatiquement (événement created du modèle).
        $request->user()->transporteur->vehicules()->create($request->validated());

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

        DB::transaction(function () use ($request, $vehicule) {
            $vehicule->update($request->validated());

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