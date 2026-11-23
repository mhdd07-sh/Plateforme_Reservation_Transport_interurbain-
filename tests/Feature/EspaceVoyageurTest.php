<?php

namespace Tests\Feature;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class EspaceVoyageurTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function reservationEnAttente(User $voyageur): Reservation
    {
        $trajet = $this->creerTrajetPublie();

        return app(ReservationService::class)->reserver($voyageur, $trajet, [$trajet->siegesLibres()->first()->id]);
    }

    private function donneesPaiement(array $surcharge = []): array
    {
        return array_merge(['moyen' => 'wave', 'numero' => '77 123 45 67', 'accepte_cgv' => '1'], $surcharge);
    }

    public function test_le_tableau_de_bord_du_voyageur_s_affiche(): void
    {
        $voyageur = User::factory()->voyageur()->create(['name' => 'Fatou Sow']);

        $this->actingAs($voyageur)->get('/dashboard')->assertRedirect(route('voyageur.dashboard'));
        $this->actingAs($voyageur)->get(route('voyageur.dashboard'))->assertOk()->assertSee('Fatou');
    }

    public function test_le_paiement_complet_confirme_la_reservation(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationEnAttente($voyageur);

        $this->actingAs($voyageur)->get(route('voyageur.paiement.create', $reservation))->assertOk();

        $this->actingAs($voyageur)
            ->post(route('voyageur.paiement.store', $reservation), $this->donneesPaiement())
            ->assertRedirect();

        $paiement = Paiement::first();
        $this->assertSame(Paiement::STATUT_INITIE, $paiement->statut_paiement);

        $this->actingAs($voyageur)->get(route('voyageur.paiement.code', $paiement))->assertOk();

        $this->actingAs($voyageur)
            ->post(route('voyageur.paiement.confirmer', $paiement), ['code' => '123456'])
            ->assertRedirect(route('voyageur.reservations.show', $reservation));

        $this->assertSame(Reservation::STATUT_CONFIRMEE, $reservation->fresh()->statut);
        $this->assertSame('wave', $reservation->fresh()->operateur_paiement);
        $this->assertSame(Paiement::STATUT_REUSSI, $paiement->fresh()->statut_paiement);

        $this->actingAs($voyageur)
            ->get(route('voyageur.reservations.show', $reservation))
            ->assertOk()
            ->assertSee($reservation->reference);
    }

    public function test_un_mauvais_code_ne_confirme_pas_la_reservation(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationEnAttente($voyageur);

        $this->actingAs($voyageur)->post(route('voyageur.paiement.store', $reservation), $this->donneesPaiement());
        $paiement = Paiement::first();

        $this->actingAs($voyageur)
            ->post(route('voyageur.paiement.confirmer', $paiement), ['code' => '000000'])
            ->assertRedirect(route('voyageur.paiement.create', $reservation));

        $this->assertSame(Reservation::STATUT_EN_ATTENTE, $reservation->fresh()->statut);
        $this->assertSame(Paiement::STATUT_ECHOUE, $paiement->fresh()->statut_paiement);
    }

    public function test_solde_insuffisant_affiche_une_erreur(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationEnAttente($voyageur);

        $this->actingAs($voyageur)
            ->post(route('voyageur.paiement.store', $reservation), $this->donneesPaiement(['numero' => '77 123 45 00']))
            ->assertSessionHas('erreur');

        $this->assertSame(Paiement::STATUT_ECHOUE, Paiement::first()->statut_paiement);
        $this->assertSame(Reservation::STATUT_EN_ATTENTE, $reservation->fresh()->statut);
    }

    public function test_un_autre_voyageur_ne_peut_pas_payer_cette_reservation(): void
    {
        $reservation = $this->reservationEnAttente(User::factory()->voyageur()->create());

        $this->actingAs(User::factory()->voyageur()->create())
            ->get(route('voyageur.paiement.create', $reservation))
            ->assertForbidden();
    }

    public function test_le_paiement_est_refuse_apres_expiration_et_libere_les_sieges(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationEnAttente($voyageur);
        Reservation::whereKey($reservation->id)->update(['expire_at' => now()->subMinute()]);

        $this->actingAs($voyageur)
            ->get(route('voyageur.paiement.create', $reservation))
            ->assertRedirect(route('voyageur.reservations.show', $reservation));

        $this->assertSame(Reservation::STATUT_EXPIREE, $reservation->fresh()->statut);
        $this->assertSame(0, $reservation->trajet->placesOccupees());
    }

    public function test_numero_invalide_refuse(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $reservation = $this->reservationEnAttente($voyageur);

        $this->actingAs($voyageur)
            ->post(route('voyageur.paiement.store', $reservation), $this->donneesPaiement(['numero' => '123']))
            ->assertSessionHasErrors('numero');
    }

    public function test_l_historique_filtre_par_statut(): void
    {
        $voyageur = User::factory()->voyageur()->create();
        $this->reservationEnAttente($voyageur);

        $this->actingAs($voyageur)->get(route('voyageur.reservations.index', ['statut' => 'en_attente']))->assertOk();
        $this->actingAs($voyageur)->get(route('voyageur.reservations.index', ['statut' => 'annulee']))->assertOk();
    }
}