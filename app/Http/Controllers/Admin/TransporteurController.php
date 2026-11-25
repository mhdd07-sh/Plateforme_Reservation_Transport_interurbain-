<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTransporteurRequest;
use App\Models\JournalAdmin;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransporteurController extends Controller
{
    private const STATUTS = [
        Transporteur::STATUT_EN_ATTENTE,
        Transporteur::STATUT_VALIDE,
        Transporteur::STATUT_SUSPENDU,
        Transporteur::STATUT_REFUSE,
    ];

    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'statut' => ['nullable', Rule::in(self::STATUTS)],
            'q' => ['nullable', 'string', 'max:100'],
            'tri' => ['nullable', Rule::in(['recents', 'anciens', 'nom'])],
        ]);

        $requete = Transporteur::query()
            ->with('user')
            ->withCount([
                'vehicules',
                'trajets as trajets_publies_count' => fn ($q) => $q->where('statut', Trajet::STATUT_PUBLIE),
            ])
            ->when($filtres['statut'] ?? null, fn ($q, $statut) => $q->where('statut', $statut))
            ->when($filtres['q'] ?? null, function ($q, $terme) {
                $q->where(function ($w) use ($terme) {
                    $w->where('nom_compagnie', 'like', "%{$terme}%")
                        ->orWhere('numero_agrement', 'like', "%{$terme}%")
                        ->orWhereHas('user', fn ($u) => $u
                            ->where('name', 'like', "%{$terme}%")
                            ->orWhere('email', 'like', "%{$terme}%")
                            ->orWhere('telephone', 'like', "%{$terme}%"));
                });
            });

        match ($filtres['tri'] ?? 'recents') {
            'anciens' => $requete->oldest(),
            'nom' => $requete->orderBy('nom_compagnie'),
            default => $requete->latest(),
        };

        $transporteurs = $requete->paginate(10)->withQueryString();

        $compteurs = Transporteur::query()->selectRaw('statut, COUNT(*) as n')->groupBy('statut')->pluck('n', 'statut');

        $stats = [
            'actives' => (int) ($compteurs[Transporteur::STATUT_VALIDE] ?? 0),
            'en_attente' => (int) ($compteurs[Transporteur::STATUT_EN_ATTENTE] ?? 0),
            'parc' => Vehicule::count(),
            'lignes' => Trajet::visibles()->select('ville_depart', 'ville_arrivee')->distinct()->get()->count(),
        ];

        return view('admin.transporteurs.index', [
            'transporteurs' => $transporteurs,
            'compteurs' => $compteurs,
            'total' => (int) $compteurs->sum(),
            'stats' => $stats,
            'flotte' => Vehicule::query()->selectRaw('type_vehicule, COUNT(*) as n')->groupBy('type_vehicule')->pluck('n', 'type_vehicule'),
            'journal' => JournalAdmin::with('admin')->where('cible_type', 'Transporteur')->latest('created_at')->limit(3)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.transporteurs.create');
    }

    public function store(StoreTransporteurRequest $request): RedirectResponse
    {
        $donnees = $request->validated();

        $transporteur = DB::transaction(function () use ($donnees) {
            $user = User::create([
                'name' => $donnees['nom_responsable'],
                'email' => $donnees['email'],
                'telephone' => $donnees['telephone'],
                'password' => Hash::make($donnees['password']),
            ]);

            $user->role = User::ROLE_TRANSPORTEUR;
            $user->email_verified_at = now(); // créé par l'administrateur : adresse considérée comme vérifiée
            $user->save();

            return Transporteur::create([
                'user_id' => $user->id,
                'type' => $donnees['type'],
                'nom_compagnie' => $donnees['nom_compagnie'] ?? null,
                'numero_agrement' => $donnees['numero_agrement'] ?? null,
                'statut' => $donnees['valider_immediatement']
                    ? Transporteur::STATUT_VALIDE
                    : Transporteur::STATUT_EN_ATTENTE,
            ]);
        });

        $transporteur->load('user');

        JournalAdmin::enregistrer('transporteur_cree', 'Compte transporteur créé : ' . $transporteur->nomAffiche(), $transporteur);

        // Le mot de passe initial n'est affiché qu'une fois, sur la page suivante.
        return redirect()
            ->route('admin.transporteurs.show', $transporteur)
            ->with('succes', 'Compte transporteur créé.')
            ->with('mot_de_passe_initial', $donnees['password']);
    }

    public function show(Request $request, Transporteur $transporteur): View
    {
        $transporteur->load('user');

        $onglet = in_array($request->query('onglet'), ['vehicules', 'trajets', 'historique'], true)
            ? $request->query('onglet')
            : 'vehicules';

        $vehicules = $transporteur->vehicules()->orderBy('immatriculation')->get();

        $trajets = $transporteur->trajets()->with('vehicule')->withCount('siegesRetenus')->get()
            ->sortByDesc(fn (Trajet $t) => $t->depart_le->timestamp)->values();

        $confirmees = $transporteur->reservations()->where('reservations.statut', Reservation::STATUT_CONFIRMEE);

        // Recettes des 6 derniers mois, agrégées en PHP (compatible avec tous les SGBD).
        $parMois = (clone $confirmees)
            ->where('reservations.created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['reservations.id', 'reservations.created_at', 'reservations.montant_total'])
            ->groupBy(fn (Reservation $r) => $r->created_at->format('Y-m'))
            ->map(fn ($groupe) => (int) $groupe->sum('montant_total'));

        $mois = collect(range(5, 0))->map(function (int $i) use ($parMois) {
            $date = now()->subMonths($i)->startOfMonth();

            return ['libelle' => $date->translatedFormat('M'), 'total' => (int) ($parMois[$date->format('Y-m')] ?? 0)];
        });

        return view('admin.transporteurs.show', [
            'transporteur' => $transporteur,
            'onglet' => $onglet,
            'vehicules' => $vehicules,
            'trajets' => $trajets,
            'journal' => JournalAdmin::with('admin')
                ->where('cible_type', 'Transporteur')->where('cible_id', $transporteur->id)
                ->latest('created_at')->limit(20)->get(),
            'stats' => [
                'vehicules' => $vehicules->count(),
                'actifs' => $vehicules->where('statut', Vehicule::STATUT_ACTIF)->count(),
                'maintenance' => $vehicules->where('statut', Vehicule::STATUT_MAINTENANCE)->count(),
                'publies' => $trajets->filter(fn (Trajet $t) => $t->statutAffiche() === Trajet::STATUT_PUBLIE)->count(),
                'confirmees' => (clone $confirmees)->count(),
                'chiffre_affaires' => (int) (clone $confirmees)->sum('reservations.montant_total'),
            ],
            'mois' => $mois,
        ]);
    }

    public function valider(Transporteur $transporteur): RedirectResponse
    {
        return $this->changer($transporteur, [Transporteur::STATUT_EN_ATTENTE], Transporteur::STATUT_VALIDE, 'transporteur_valide', 'Transporteur validé');
    }

    public function refuser(Transporteur $transporteur): RedirectResponse
    {
        return $this->changer($transporteur, [Transporteur::STATUT_EN_ATTENTE], Transporteur::STATUT_REFUSE, 'transporteur_refuse', 'Demande refusée');
    }

    public function suspendre(Transporteur $transporteur): RedirectResponse
    {
        return $this->changer($transporteur, [Transporteur::STATUT_VALIDE], Transporteur::STATUT_SUSPENDU, 'transporteur_suspendu', 'Transporteur suspendu');
    }

    public function reactiver(Transporteur $transporteur): RedirectResponse
    {
        return $this->changer(
            $transporteur,
            [Transporteur::STATUT_SUSPENDU, Transporteur::STATUT_REFUSE],
            Transporteur::STATUT_VALIDE,
            'transporteur_reactive',
            'Transporteur réactivé'
        );
    }

    /**
     * Change le statut si la transition est permise, et l'inscrit au journal.
     * Un transporteur suspendu disparaît de la recherche ; ses réservations déjà payées restent valides.
     *
     * @param string[] $depuis
     */
    private function changer(Transporteur $transporteur, array $depuis, string $vers, string $action, string $message): RedirectResponse
    {
        if (! in_array($transporteur->statut, $depuis, true)) {
            return back()->with('erreur', "Cette action n'est pas possible pour un transporteur « {$transporteur->libelleStatut()} ».");
        }

        $transporteur->update(['statut' => $vers]);
        $transporteur->load('user');

        JournalAdmin::enregistrer($action, $message . ' : ' . $transporteur->nomAffiche(), $transporteur);

        return back()->with('succes', $message . '.');
    }
}