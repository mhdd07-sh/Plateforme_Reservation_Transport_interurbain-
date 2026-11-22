<?php

namespace Tests\Feature;

use App\Models\Paiement;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class PaiementServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    private function service(): PaiementServiceInterface
    {
        return app(PaiementServiceInterface::class);
    }

    public function test_initier_cree_un_paiement_en_attente_de_confirmation(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie(18, 2000), 2);

        $resultat = $this->service()->initier($reservation, Paiement::MOYEN_WAVE, '771234567');

        $this->assertTrue($resultat->succes);
        $this->assertSame(Paiement::STATUT_INITIE, $resultat->paiement->statut_paiement);
        $this->assertSame(4000, $resultat->paiement->montant);
        $this->assertNotNull($resultat->paiement->reference_transaction);
    }

    public function test_un_numero_se_terminant_par_00_simule_un_solde_insuffisant(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());

        $resultat = $this->service()->initier($reservation, Paiement::MOYEN_ORANGE_MONEY, '771234500');

        $this->assertFalse($resultat->succes);
        $this->assertSame(Paiement::STATUT_ECHOUE, $resultat->paiement->statut_paiement);
    }

    public function test_le_bon_code_confirme_le_paiement(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());
        $paiement = $this->service()->initier($reservation, Paiement::MOYEN_WAVE, '771234567')->paiement;

        $resultat = $this->service()->confirmer($paiement, '123456');

        $this->assertTrue($resultat->succes);
        $this->assertSame(Paiement::STATUT_REUSSI, $paiement->fresh()->statut_paiement);
        $this->assertNotNull($paiement->fresh()->date_paiement);
    }

    public function test_un_mauvais_code_fait_echouer_le_paiement(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());
        $paiement = $this->service()->initier($reservation, Paiement::MOYEN_WAVE, '771234567')->paiement;

        $resultat = $this->service()->confirmer($paiement, '000000');

        $this->assertFalse($resultat->succes);
        $this->assertSame(Paiement::STATUT_ECHOUE, $paiement->fresh()->statut_paiement);
    }

    public function test_seul_un_paiement_reussi_peut_etre_rembourse(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());
        $paiement = $this->service()->initier($reservation, Paiement::MOYEN_WAVE, '771234567')->paiement;

        $this->assertFalse($this->service()->rembourser($paiement)->succes);

        $this->service()->confirmer($paiement, '123456');
        $this->assertTrue($this->service()->rembourser($paiement->fresh())->succes);
        $this->assertSame(Paiement::STATUT_REMBOURSE, $paiement->fresh()->statut_paiement);
    }

    public function test_une_carte_n_est_jamais_stockee_en_entier(): void
    {
        $reservation = $this->creerReservationSimple($this->creerTrajetPublie());

        $paiement = $this->service()->initier($reservation, Paiement::MOYEN_CARTE, '4111111111111111')->paiement;

        $this->assertSame('**** 1111', $paiement->numero_paiement);
    }
}