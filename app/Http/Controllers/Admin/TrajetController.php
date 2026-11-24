<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalAdmin;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Exceptions\AnnulationImpossibleException;
use App\Services\AnnulationService;


class TrajetController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'transporteur' => ['nullable', 'integer'],
            'statut' => ['nullable', Rule::in(['publie', 'complet', 'brouillon', 'annule', 'termine'])],
            'date' => ['nullable', 'date'],
        ]);

        $statut = $filtres['statut'] ?? null;
        $date = $filtres['date'] ?? null;

        $requete = Trajet::query()
            ->with(['vehicule', 'transporteur.user'])
            ->withCount('siegesRetenus')
            ->when($filtres['q'] ?? null, function ($q, $terme) {
                $q->where(fn ($w) => $w->where('ville_depart', 'like', "%{$terme}%")->orWhere('ville_arrivee', 'like', "%{$terme}%"));
            })
            ->when($filtres['transporteur'] ?? null, fn ($q, $id) => $q->where('transporteur_id', $id))
            ->when($date, fn ($q, $jour) => $q->whereDate('date_depart', $jour));

        match ($statut) {
            'publie' => $requete->publies()->aVenir(),
            'complet' => $requete->publies()->aVenir()->complets(),
            'brouillon' => $requete->where('statut', Trajet::STATUT_BROUILLON),
            'annule' => $requete->where('statut', Trajet::STATUT_ANNULE),
            'termine' => $requete->publies()->passes(),
            default => null,
        };

        // Sans filtre de statut ni de date : départs à venir uniquement.
        if (! $statut && ! $date) {
            $requete->aVenir();
        }

        $trajets = $requete->orderBy('date_depart')->orderBy('heure_depart')->paginate(10)->withQueryString();

        $stats = [
            'publies' => Trajet::publies()->aVenir()->count(),
            'brouillons' => Trajet::where('statut', Trajet::STATUT_BROUILLON)->count(),
            'complets' => Trajet::publies()->aVenir()->complets()->count(),
            'annules' => Trajet::where('statut', Trajet::STATUT_ANNULE)->count(),
            'passagers_aujourdhui' => (int) Trajet::publies()->whereDate('date_depart', today())
                ->withCount('siegesRetenus')->get()->sum('sieges_retenus_count'),
        ];

        return view('admin.trajets.index', [
            'trajets' => $trajets,
            'stats' => $stats,
            'transporteurs' => Transporteur::with('user')->orderBy('nom_compagnie')->get(),
        ]);
    }

    public function show(Trajet $trajet): View
    {
        $trajet->load(['vehicule', 'transporteur.user']);

        $reservations = $trajet->reservations()->with(['user', 'sieges'])->latest()->get();

        return view('admin.trajets.show', [
            'trajet' => $trajet,
            'reservations' => $reservations,
            'occupes' => $trajet->placesOccupees(),
            'revenus' => (int) $reservations->where('statut', Reservation::STATUT_CONFIRMEE)->sum('montant_total'),
            'actives' => $reservations->whereIn('statut', [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_CONFIRMEE])->count(),
        ]);
    }

    /** Retire un trajet publié de la vente (retour en brouillon), seulement s'il n'a aucune réservation active. */
    public function retirer(Trajet $trajet): RedirectResponse
    {
        if ($trajet->statut !== Trajet::STATUT_PUBLIE) {
            return back()->with('erreur', 'Seul un trajet publié peut être retiré de la vente.');
        }

        $aDesReservations = $trajet->reservations()
            ->whereIn('statut', [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_CONFIRMEE])
            ->exists();

        if ($aDesReservations) {
            return back()->with('erreur', 'Ce trajet a des réservations : il ne peut pas être retiré de la vente.');
        }

        $trajet->update(['statut' => Trajet::STATUT_BROUILLON]);

        JournalAdmin::enregistrer(
            'trajet_retire',
            "Trajet retiré de la vente : {$trajet->ville_depart} → {$trajet->ville_arrivee}",
            $trajet
        );

        return back()->with('succes', 'Trajet retiré de la vente (repassé en brouillon).');
    }

        /** Annulation d'un trajet par l'administrateur (panne, incident) : réservations remboursées. */
    public function annuler(Request $request, Trajet $trajet, AnnulationService $annulations): RedirectResponse
    {
        $donnees = $request->validate(
            ['motif' => ['nullable', 'string', 'max:255'], 'confirmation' => ['accepted']],
            ['confirmation.accepted' => "Cochez la case pour confirmer l'annulation."]
        );

        try {
            $bilan = $annulations->annulerTrajet($trajet, $donnees['motif'] ?? null);
        } catch (AnnulationImpossibleException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        JournalAdmin::enregistrer(
            'trajet_annule',
            "Trajet annulé : {$trajet->ville_depart} → {$trajet->ville_arrivee} ({$bilan['remboursees']} remboursement(s))",
            $trajet
        );

        return back()->with('succes', "Trajet annulé : {$bilan['remboursees']} réservation(s) remboursée(s), {$bilan['liberees']} retenue(s) libérée(s).");
    }
}