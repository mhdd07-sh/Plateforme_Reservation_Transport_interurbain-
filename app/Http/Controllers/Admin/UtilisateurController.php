<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalAdmin;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UtilisateurController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(User::ROLES)],
            'statut' => ['nullable', Rule::in(['actif', 'desactive'])],
            'tri' => ['nullable', Rule::in(['recents', 'anciens', 'alpha', 'activite'])],
            'utilisateur' => ['nullable', 'integer'],
        ]);

        $requete = User::query()
            ->with('transporteur')
            ->withCount('reservations')
            ->when($filtres['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when($filtres['statut'] ?? null, fn ($q, $statut) => $q->where('actif', $statut === 'actif'))
            ->when($filtres['q'] ?? null, function ($q, $terme) {
                $q->where(function ($w) use ($terme) {
                    $w->where('name', 'like', "%{$terme}%")
                        ->orWhere('email', 'like', "%{$terme}%")
                        ->orWhere('telephone', 'like', "%{$terme}%");
                });
            });

        match ($filtres['tri'] ?? 'recents') {
            'alpha' => $requete->orderBy('name'),
            'activite' => $requete->orderByDesc('reservations_count'),
            'anciens' => $requete->oldest(),
            default => $requete->latest(),
        };

        $utilisateurs = $requete->paginate(10)->withQueryString();

        // Nombre de trajets gérés, pour les transporteurs de la page.
        $idsTransporteurs = $utilisateurs->getCollection()->pluck('transporteur.id')->filter()->all();
        $trajetsGeres = Trajet::whereIn('transporteur_id', $idsTransporteurs)
            ->selectRaw('transporteur_id, COUNT(*) as n')
            ->groupBy('transporteur_id')
            ->pluck('n', 'transporteur_id');

        $parRole = User::query()->selectRaw('role, COUNT(*) as n')->groupBy('role')->pluck('n', 'role');
        $parStatut = User::query()->selectRaw('actif, COUNT(*) as n')->groupBy('actif')->pluck('n', 'actif');

        // Fiche de l'utilisateur sélectionné.
        $selection = null;
        $detail = null;

        if (! empty($filtres['utilisateur'])) {
            $selection = User::with('transporteur')->find($filtres['utilisateur']);

            if ($selection) {
                $confirmees = fn () => $selection->reservations()->where('statut', Reservation::STATUT_CONFIRMEE);

                $detail = [
                    'voyages' => $confirmees()->count(),
                    'paye' => (int) $confirmees()->sum('montant_total'),
                    'dernieres' => $selection->reservations()->with('trajet')->latest()->limit(3)->get(),
                    'trajets' => $selection->transporteur ? $selection->transporteur->trajets()->count() : 0,
                ];
            }
        }

        return view('admin.utilisateurs.index', [
            'utilisateurs' => $utilisateurs,
            'trajetsGeres' => $trajetsGeres,
            'parRole' => $parRole,
            'parStatut' => $parStatut,
            'total' => (int) $parRole->sum(),
            'selection' => $selection,
            'detail' => $detail,
        ]);
    }

    public function desactiver(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('erreur', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        if (! $user->actif) {
            return back()->with('erreur', 'Ce compte est déjà désactivé.');
        }

        $user->actif = false;
        $user->save();

        // Un transporteur désactivé n'apparaît plus dans la recherche.
        if ($user->transporteur && $user->transporteur->statut === Transporteur::STATUT_VALIDE) {
            $user->transporteur->update(['statut' => Transporteur::STATUT_SUSPENDU]);
        }

        JournalAdmin::enregistrer('utilisateur_desactive', 'Compte désactivé : ' . $user->name, $user);

        return redirect()
            ->route('admin.utilisateurs.index', ['utilisateur' => $user->id])
            ->with('succes', 'Compte désactivé : l\'utilisateur ne peut plus se connecter.');
    }

    public function reactiver(User $user): RedirectResponse
    {
        if ($user->actif) {
            return back()->with('erreur', 'Ce compte est déjà actif.');
        }

        $user->actif = true;
        $user->save();

        JournalAdmin::enregistrer('utilisateur_reactive', 'Compte réactivé : ' . $user->name, $user);

        return redirect()
            ->route('admin.utilisateurs.index', ['utilisateur' => $user->id])
            ->with('succes', 'Compte réactivé.');
    }
}