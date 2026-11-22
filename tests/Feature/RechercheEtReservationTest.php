<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Trajet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class RechercheEtReservationTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_la_recherche_affiche_les_trajets_publies_et_masque_les_brouillons(): void
    {
        $publie = $this->creerTrajetPublie();
        $publie->update(['ville_depart' => 'Bambey', 'ville_arrivee' => 'Nioro']);

        $brouillon = $this->creerTrajetPublie();
        $brouillon->update(['ville_depart' => 'Podor', 'ville_arrivee' => 'Bakel', 'statut' => Trajet::STATUT_BROUILLON]);

        $this->get('/trajets')->assertOk()->assertSee('Bambey')->assertDontSee('Podor');
        $this->get('/trajets?depart=Bambey')->assertOk()->assertSee('Bambey');
        $this->get('/trajets/' . $brouillon->id)->assertNotFound();
        $this->get('/trajets/' . $publie->id)->assertOk()->assertSee('Bambey');
    }

    public function test_un_voyageur_reserve_puis_voit_sa_reservation(): void
    {
        $trajet = $this->creerTrajetPublie();
        $voyageur = User::factory()->voyageur()->create();
        $siege = $trajet->siegesLibres()->first()->id;

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.store', $trajet), ['sieges' => [$siege]])
            ->assertRedirect(route('voyageur.reservations.show', Reservation::first()));

        $this->actingAs($voyageur)
            ->get(route('voyageur.reservations.show', Reservation::first()))
            ->assertOk()
            ->assertSee(Reservation::first()->reference);
    }

    public function test_un_voyageur_ne_voit_pas_la_reservation_d_un_autre(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());

        $this->actingAs(User::factory()->voyageur()->create())
            ->get(route('voyageur.reservations.show', $reservation))
            ->assertForbidden();
    }

    public function test_un_transporteur_ne_peut_pas_utiliser_la_reservation(): void
    {
        $trajet = $this->creerTrajetPublie();

        $this->actingAs(User::factory()->transporteur()->create())
            ->post(route('voyageur.reservations.store', $trajet), ['sieges' => [1]])
            ->assertForbidden();
    }

    public function test_abandonner_une_reservation_libere_les_sieges(): void
    {
        $trajet = $this->creerTrajetPublie();
        $voyageur = User::factory()->voyageur()->create();

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.store', $trajet), ['sieges' => [$trajet->siegesLibres()->first()->id]]);

        $reservation = Reservation::first();
        $this->assertSame(1, $trajet->placesOccupees());

        $this->actingAs($voyageur)
            ->post(route('voyageur.reservations.abandonner', $reservation))
            ->assertRedirect(route('voyageur.reservations.index'));

        $this->assertSame(0, $trajet->placesOccupees());
    }
}