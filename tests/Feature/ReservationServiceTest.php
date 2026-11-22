<?php

namespace Tests\Feature;

use App\Exceptions\ReservationImpossibleException;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    private function service(): ReservationService
    {
        return app(ReservationService::class);
    }

    public function test_reserve_des_sieges_et_calcule_le_montant(): void
    {
        $trajet = $this->creerTrajetPublie(18, 2000);
        $ids = $trajet->siegesLibres()->limit(2)->pluck('id')->all();

        $reservation = $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, $ids);

        $this->assertSame(4000, $reservation->montant_total);
        $this->assertSame(Reservation::STATUT_EN_ATTENTE, $reservation->statut);
        $this->assertSame(2, $trajet->placesOccupees());
        $this->assertSame(16, $trajet->fresh()->placesDisponibles());
    }

    public function test_un_siege_deja_reserve_ne_peut_pas_etre_reserve_deux_fois(): void
    {
        $trajet = $this->creerTrajetPublie();
        $siege = $trajet->siegesLibres()->first()->id;

        $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, [$siege]);

        $this->expectException(ReservationImpossibleException::class);
        $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, [$siege]);
    }

    public function test_un_siege_d_un_autre_vehicule_est_refuse(): void
    {
        $trajet = $this->creerTrajetPublie();
        $siegeEtranger = $this->creerTrajetPublie()->siegesLibres()->first()->id;

        $this->expectException(ReservationImpossibleException::class);
        $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, [$siegeEtranger]);
    }

    public function test_une_retenue_expiree_libere_les_sieges_pour_un_autre_voyageur(): void
    {
        $trajet = $this->creerTrajetPublie();
        $siege = $trajet->siegesLibres()->first()->id;

        $premiere = $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, [$siege]);
        Reservation::whereKey($premiere->id)->update(['expire_at' => now()->subMinute()]);

        $seconde = $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, [$siege]);

        $this->assertSame(Reservation::STATUT_EXPIREE, $premiere->fresh()->statut);
        $this->assertSame(Reservation::STATUT_EN_ATTENTE, $seconde->statut);
    }

    public function test_un_transporteur_ne_peut_pas_reserver(): void
    {
        $trajet = $this->creerTrajetPublie();

        $this->expectException(ReservationImpossibleException::class);
        $this->service()->reserver(
            User::factory()->transporteur()->create(),
            $trajet,
            [$trajet->siegesLibres()->first()->id]
        );
    }

    public function test_un_voyageur_est_limite_a_trois_reservations_en_attente(): void
    {
        $trajet = $this->creerTrajetPublie();
        $voyageur = User::factory()->voyageur()->create();
        $ids = $trajet->siegesLibres()->limit(4)->pluck('id')->all();

        foreach (array_slice($ids, 0, 3) as $id) {
            $this->service()->reserver($voyageur, $trajet, [$id]);
        }

        $this->expectException(ReservationImpossibleException::class);
        $this->service()->reserver($voyageur, $trajet, [$ids[3]]);
    }

    public function test_pas_plus_de_cinq_sieges_par_reservation(): void
    {
        $trajet = $this->creerTrajetPublie();
        $ids = $trajet->siegesLibres()->limit(6)->pluck('id')->all();

        $this->expectException(ReservationImpossibleException::class);
        $this->service()->reserver(User::factory()->voyageur()->create(), $trajet, $ids);
    }
}