<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Trois réservations de démonstration : confirmée, en attente, annulée (remboursée).
 */
class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $voyageurs = User::where('role', User::ROLE_VOYAGEUR)->orderBy('id')->get();

        $trajets = Trajet::publies()->aVenir()
            ->orderBy('date_depart')->orderBy('id')
            ->take(2)->get();

        // 1. Réservation confirmée et payée (2 sièges)
        $this->creer($voyageurs[0], $trajets[0], 2, Reservation::STATUT_CONFIRMEE, Paiement::STATUT_REUSSI, Paiement::MOYEN_WAVE);

        // 2. Réservation en attente de paiement sur le même trajet (1 siège retenu 10 min)
        $this->creer($voyageurs[1], $trajets[0], 1, Reservation::STATUT_EN_ATTENTE, null, null);

        // 3. Réservation annulée et remboursée (aucun siège retenu)
        $this->creer($voyageurs[2], $trajets[1], 2, Reservation::STATUT_ANNULEE, Paiement::STATUT_REMBOURSE, Paiement::MOYEN_ORANGE_MONEY);
    }

    private function creer(User $voyageur, Trajet $trajet, int $places, string $statut, ?string $statutPaiement, ?string $moyen): void
    {
        $reservation = Reservation::create([
            'user_id' => $voyageur->id,
            'trajet_id' => $trajet->id,
            'nombre_places' => $places,
            'montant_total' => $places * $trajet->prix_unitaire,
            'operateur_paiement' => $moyen,
            'statut' => $statut,
        ]);

        // Seules les réservations actives retiennent des sièges.
        if (in_array($statut, [Reservation::STATUT_EN_ATTENTE, Reservation::STATUT_CONFIRMEE], true)) {
            $reservation->attacherSieges(
                $trajet->siegesLibres()->limit($places)->pluck('id')->all()
            );
        }

        if ($statutPaiement !== null) {
            Paiement::create([
                'reservation_id' => $reservation->id,
                'montant' => $reservation->montant_total,
                'moyen_paiement' => $moyen,
                'numero_paiement' => $voyageur->telephone,
                'statut_paiement' => $statutPaiement,
                'reference_transaction' => 'TX-' . strtoupper(Str::random(10)),
                'date_paiement' => now(),
            ]);
        }
    }
}