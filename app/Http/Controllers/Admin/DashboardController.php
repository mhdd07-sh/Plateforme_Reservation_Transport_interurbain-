<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalAdmin;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use App\Support\Telephone;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $jours = (int) $request->query('periode', 30);
        $jours = in_array($jours, [7, 30, 90], true) ? $jours : 30;

        $debutMois = now()->startOfMonth();
        $debutMoisPrecedent = now()->subMonthNoOverflow()->startOfMonth();

        $confirmees = fn () => Reservation::where('statut', Reservation::STATUT_CONFIRMEE);

        $revenusMois = (int) $confirmees()->where('created_at', '>=', $debutMois)->sum('montant_total');
        $revenusPrecedent = (int) $confirmees()->whereBetween('created_at', [$debutMoisPrecedent, $debutMois])->sum('montant_total');

        // Taux de remplissage des départs à venir (calculé en PHP : peu de lignes).
        $futurs = Trajet::visibles()->with('vehicule')->withCount('siegesRetenus')->get();
        $capacite = (int) $futurs->sum(fn (Trajet $t) => $t->vehicule->capacite);
        $occupes = (int) $futurs->sum('sieges_retenus_count');

        $stats = [
            'voyageurs' => User::where('role', User::ROLE_VOYAGEUR)->count(),
            'voyageurs_mois' => User::where('role', User::ROLE_VOYAGEUR)->where('created_at', '>=', $debutMois)->count(),
            'transporteurs' => Transporteur::where('statut', Transporteur::STATUT_VALIDE)->count(),
            'transporteurs_mois' => Transporteur::where('statut', Transporteur::STATUT_VALIDE)->where('created_at', '>=', $debutMois)->count(),
            'en_attente' => Transporteur::where('statut', Transporteur::STATUT_EN_ATTENTE)->count(),
            'trajets_publies' => Trajet::publies()->aVenir()->count(),
            'trajets_semaine' => Trajet::publies()->aVenir()->where('created_at', '>=', now()->startOfWeek())->count(),
            'reservations' => $confirmees()->count(),
            'remplissage' => $capacite > 0 ? (int) round($occupes / $capacite * 100) : 0,
            'revenus_mois' => $revenusMois,
            'evolution' => $revenusPrecedent > 0 ? (int) round(($revenusMois - $revenusPrecedent) / $revenusPrecedent * 100) : null,
        ];

        // Réservations confirmées par jour sur la période.
        $parJour = $confirmees()
            ->where('created_at', '>=', now()->subDays($jours - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as jour, COUNT(*) as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $serie = collect(range($jours - 1, 0))->map(function (int $i) use ($parJour) {
            $date = now()->subDays($i)->startOfDay();

            return ['date' => $date, 'total' => (int) ($parJour[$date->toDateString()] ?? 0)];
        })->values();

        // Lignes les plus demandées.
        $lignes = DB::table('reservations')
            ->join('trajets', 'trajets.id', '=', 'reservations.trajet_id')
            ->where('reservations.statut', Reservation::STATUT_CONFIRMEE)
            ->select('trajets.ville_depart', 'trajets.ville_arrivee', DB::raw('COUNT(*) as total'))
            ->groupBy('trajets.ville_depart', 'trajets.ville_arrivee')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'jours' => $jours,
            'serie' => $serie,
            'lignes' => $lignes,
            'activite' => $this->activiteRecente(),
        ]);
    }

    /** Flux des derniers événements, tirés des tables existantes et du journal admin. */
    private function activiteRecente(): Collection
    {
        $evenements = collect();

        JournalAdmin::with('admin')->latest('created_at')->limit(6)->get()->each(function (JournalAdmin $j) use ($evenements) {
            $evenements->push([
                'icone' => 'verified_user', 'tone' => 'success', 'titre' => $j->libelle,
                'detail' => 'Action de ' . ($j->admin?->name ?? 'l\'administrateur'),
                'badge' => 'Admin', 'badge_tone' => 'neutral', 'date' => $j->created_at,
            ]);
        });

        User::where('role', User::ROLE_VOYAGEUR)->latest()->limit(4)->get()->each(function (User $u) use ($evenements) {
            $evenements->push([
                'icone' => 'person_add', 'tone' => 'info', 'titre' => 'Nouvelle inscription voyageur : ' . $u->name,
                'detail' => $u->telephone ? '+221 ' . Telephone::formater($u->telephone) : $u->email,
                'badge' => 'Utilisateur', 'badge_tone' => 'info', 'date' => $u->created_at,
            ]);
        });

        Transporteur::with('user')->latest()->limit(4)->get()->each(function (Transporteur $t) use ($evenements) {
            $evenements->push([
                'icone' => 'assignment_ind', 'tone' => 'warning',
                'titre' => 'Dossier ' . $t->nomAffiche() . ' ' . ($t->statut === Transporteur::STATUT_EN_ATTENTE ? 'soumis pour validation' : 'enregistré'),
                'detail' => 'Compte ' . mb_strtolower($t->libelleType()),
                'badge' => $t->libelleStatut(), 'badge_tone' => $t->toneStatut(), 'date' => $t->created_at,
            ]);
        });

        Trajet::with('transporteur.user')->latest()->limit(4)->get()->each(function (Trajet $t) use ($evenements) {
            $evenements->push([
                'icone' => 'alt_route', 'tone' => 'success',
                'titre' => $t->transporteur->nomAffiche() . ' a créé un trajet',
                'detail' => $t->ville_depart . ' → ' . $t->ville_arrivee,
                'badge' => $t->libelleStatut(), 'badge_tone' => $t->toneStatut(), 'date' => $t->created_at,
            ]);
        });

        Reservation::with('trajet')->where('statut', Reservation::STATUT_CONFIRMEE)->latest()->limit(4)->get()->each(function (Reservation $r) use ($evenements) {
            $evenements->push([
                'icone' => 'check_circle', 'tone' => 'success',
                'titre' => 'Réservation ' . $r->reference . ' confirmée',
                'detail' => $r->nombre_places . ' place(s) · ' . $r->trajet->ville_depart . ' → ' . $r->trajet->ville_arrivee,
                'badge' => 'Confirmé', 'badge_tone' => 'success', 'date' => $r->created_at,
            ]);
        });

        return $evenements->sortByDesc('date')->take(8)->values();
    }
}