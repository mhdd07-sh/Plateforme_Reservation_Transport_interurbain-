<?php

namespace Tests\Feature;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use App\Services\Paiement\PaiementServiceInterface;
use App\Services\Paiement\ResultatPaiement;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class AnnulationRemboursementTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** Réservation confirmée et payée, comme après le parcours complet. */
    private function reservationPayee(User $voyageur, Trajet $trajet): Reservation
    {
        $reservation = app(ReservationService::class)->reserver($voyageur, $trajet, [$trajet->siegesLibres()->first()->id]);

        $service = app(PaiementServiceInterface::class);
        $paiement = $service->initier($reservation, Paiement::MOYEN_WAVE, '771234567')->paiement;
        $service->confirmer($paiement, '123456');

        $reservation->update(['operateur_paiement' => 'wave']);
        $reservation->confirmer();

        return $reservation->fresh();
    }

    public function test_le_voyageur_annule_et_est_rembourse(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $trajet = $this->creerTrajetPublie();
        $reservation = $this->reservationPayee($voyageur, $trajet);

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.annuler', $reservation))
            ->assertRedirect(route('voyageur.reservations.show', $reservation))
            ->assertSessionHas('succes');

        $reservation->refresh();

        $this->assertSame(Reservation::STATUT_ANNULEE, $reservation->statut);
        $this->assertNotNull($reservation->annulee_le);
        $this->assertSame(Paiement::STATUT_REMBOURSE, $reservation->paiement->statut_paiement);
        $this->assertSame(0, $trajet->placesOccupees());
    }

    public function test_pas_d_annulation_moins_de_24h_avant_le_depart(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $trajet = $this->creerTrajetPublie();
        $reservation = $this->reservationPayee($voyageur, $trajet);

        $bientot = now()->addHours(5);
        $trajet->update(['date_depart' => $bientot->toDateString(), 'heure_depart' => $bientot->format('H:i:s')]);

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.annuler', $reservation))
            ->assertForbidden();

        $this->assertSame(Reservation::STATUT_CONFIRMEE, $reservation->fresh()->statut);
    }

    public function test_un_autre_voyageur_ne_peut_pas_annuler(): void
    {
        $reservation = $this->reservationPayee(User::factory()->voyageur()->create(), $this->creerTrajetPublie());

        $this->actingAs(User::factory()->voyageur()->create())
            ->post(route('voyageur.reservations.annuler', $reservation))
            ->assertForbidden();
    }

    public function test_une_reservation_deja_annulee_ne_peut_pas_etre_annulee_deux_fois(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationPayee($voyageur, $this->creerTrajetPublie());

        $this->actingAs($voyageur)->post(route('voyageur.reservations.annuler', $reservation));
        $this->actingAs($voyageur)->post(route('voyageur.reservations.annuler', $reservation))->assertForbidden();

        $this->assertSame(1, Paiement::where('statut_paiement', Paiement::STATUT_REMBOURSE)->count());
    }

    public function test_un_remboursement_en_echec_n_annule_rien(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationPayee($voyageur, $this->creerTrajetPublie());

        // Service de paiement dont le remboursement échoue toujours.
        $this->app->bind(PaiementServiceInterface::class, fn () => new class implements PaiementServiceInterface {
            public function initier(Reservation $reservation, string $moyen, string $numero): ResultatPaiement
            {
                throw new \LogicException('non utilisé');
            }

            public function confirmer(Paiement $paiement, string $code): ResultatPaiement
            {
                throw new \LogicException('non utilisé');
            }

            public function rembourser(Paiement $paiement): ResultatPaiement
            {
                return new ResultatPaiement(false, 'Opérateur indisponible.', $paiement);
            }
        });

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.annuler', $reservation))
            ->assertSessionHas('erreur');

        $this->assertSame(Reservation::STATUT_CONFIRMEE, $reservation->fresh()->statut);
        $this->assertSame(Paiement::STATUT_REUSSI, $reservation->fresh()->paiement->statut_paiement);
    }

    public function test_le_transporteur_annule_un_trajet_avec_reservations(): void
    {
        $trajet = $this->creerTrajetPublie();
        $payee = $this->reservationPayee(User::factory()->voyageur()->create(), $trajet);

        $enAttente = app(ReservationService::class)->reserver(
            User::factory()->voyageur()->create(),
            $trajet,
            [$trajet->siegesLibres()->first()->id]
        );

        $this->actingAs($trajet->transporteur->user)
            ->post(route('transporteur.trajets.annuler', $trajet), ['motif' => 'Panne du véhicule', 'confirmation' => '1'])
            ->assertRedirect(route('transporteur.trajets.index'))
            ->assertSessionHas('succes');

        $this->assertSame(Trajet::STATUT_ANNULE, $trajet->fresh()->statut);
        $this->assertSame('Panne du véhicule', $trajet->fresh()->motif_annulation);

        $this->assertSame(Reservation::STATUT_ANNULEE, $payee->fresh()->statut);
        $this->assertSame('Panne du véhicule', $payee->fresh()->motif_annulation);
        $this->assertSame(Paiement::STATUT_REMBOURSE, $payee->fresh()->paiement->statut_paiement);
        $this->assertSame(Reservation::STATUT_ANNULEE, $enAttente->fresh()->statut);
        $this->assertSame(0, $trajet->placesOccupees());
    }

    public function test_l_annulation_d_un_trajet_exige_la_confirmation(): void
    {
        $trajet = $this->creerTrajetPublie();

        $this->actingAs($trajet->transporteur->user)
            ->post(route('transporteur.trajets.annuler', $trajet))
            ->assertSessionHasErrors('confirmation');

        $this->assertSame(Trajet::STATUT_PUBLIE, $trajet->fresh()->statut);
    }

    public function test_un_trajet_deja_parti_ne_peut_pas_etre_annule(): void
    {
        $trajet = $this->creerTrajetPublie();
        $trajet->update(['date_depart' => now()->subDay()->toDateString()]);

        $this->actingAs($trajet->transporteur->user)
            ->post(route('transporteur.trajets.annuler', $trajet), ['confirmation' => '1'])
            ->assertForbidden();
    }

    public function test_un_autre_transporteur_ne_peut_pas_annuler_ce_trajet(): void
    {
        $trajet = $this->creerTrajetPublie();
        $autre = $this->creerTrajetPublie()->transporteur->user;

        $this->actingAs($autre)
            ->post(route('transporteur.trajets.annuler', $trajet), ['confirmation' => '1'])
            ->assertForbidden();
    }

    public function test_l_admin_annule_un_trajet_et_l_action_est_journalisee(): void
    {
        $trajet = $this->creerTrajetPublie();
        $reservation = $this->reservationPayee(User::factory()->voyageur()->create(), $trajet);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.trajets.annuler', $trajet), ['motif' => 'Panne compresseur', 'confirmation' => '1'])
            ->assertSessionHas('succes');

        $this->assertSame(Trajet::STATUT_ANNULE, $trajet->fresh()->statut);
        $this->assertSame(Reservation::STATUT_ANNULEE, $reservation->fresh()->statut);
        $this->assertSame(1, \App\Models\JournalAdmin::where('action', 'trajet_annule')->count());
    }

    public function test_la_page_d_une_reservation_annulee_s_affiche(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationPayee($voyageur, $this->creerTrajetPublie());

        $this->actingAs($voyageur)->post(route('voyageur.reservations.annuler', $reservation));

        $this->actingAs($voyageur)
            ->get(route('voyageur.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Annulée');
    }
}