<?php

namespace Tests\Feature;

use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use App\Models\Vehicule;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class EspaceTransporteurTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array{0: User, 1: Transporteur} */
    private function transporteurValide(): array
    {
        $user = User::factory()->transporteur()->create();
        $transporteur = Transporteur::factory()->create(['user_id' => $user->id]);

        return [$user, $transporteur];
    }

    private function donneesTrajet(int $vehiculeId, array $surcharge = []): array
    {
        return array_merge([
            'vehicule_id' => $vehiculeId,
            'ville_depart' => 'Dakar',
            'ville_arrivee' => 'Thiès',
            'date_depart' => now()->addDay()->toDateString(),
            'heure_depart' => '09:00',
            'prix_unitaire' => 2500,
            'action' => 'publier',
        ], $surcharge);
    }

    public function test_le_tableau_de_bord_s_affiche_pour_un_transporteur_valide(): void
    {
        [$user, $transporteur] = $this->transporteurValide();

        $vehicule = Vehicule::factory()->create(['transporteur_id' => $transporteur->id]);
        Trajet::factory()->create([
            'transporteur_id' => $transporteur->id,
            'vehicule_id' => $vehicule->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'statut' => Trajet::STATUT_PUBLIE,
        ]);

        $this->actingAs($user)->get(route('transporteur.dashboard'))->assertOk()->assertSee('Prochains départs');
    }

    public function test_un_compte_en_attente_voit_le_message_de_validation(): void
    {
        $user = User::factory()->transporteur()->create();
        Transporteur::factory()->enAttente()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('transporteur.dashboard'))->assertOk()->assertSee('en attente de validation');
        $this->actingAs($user)->get(route('transporteur.vehicules.index'))->assertRedirect(route('transporteur.dashboard'));
    }

    public function test_la_liste_des_trajets_se_filtre_par_statut(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        $v1 = Vehicule::factory()->create(['transporteur_id' => $transporteur->id]);
        $v2 = Vehicule::factory()->create(['transporteur_id' => $transporteur->id]);

        Trajet::factory()->create([
            'transporteur_id' => $transporteur->id, 'vehicule_id' => $v1->id,
            'ville_depart' => 'Bambey', 'ville_arrivee' => 'Nioro',
            'date_depart' => now()->addDays(2)->toDateString(), 'statut' => Trajet::STATUT_PUBLIE,
        ]);
        Trajet::factory()->create([
            'transporteur_id' => $transporteur->id, 'vehicule_id' => $v2->id,
            'ville_depart' => 'Podor', 'ville_arrivee' => 'Bakel',
            'date_depart' => now()->addDays(2)->toDateString(), 'statut' => Trajet::STATUT_BROUILLON,
        ]);

        $this->actingAs($user)->get(route('transporteur.trajets.index', ['statut' => 'brouillon']))
            ->assertOk()->assertSee('Podor')->assertDontSee('Bambey');

        $this->actingAs($user)->get(route('transporteur.trajets.index', ['statut' => 'publie']))
            ->assertOk()->assertSee('Bambey')->assertDontSee('Podor');
    }

    public function test_creation_et_publication_d_un_trajet(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        $vehicule = Vehicule::factory()->create(['transporteur_id' => $transporteur->id]);

        $this->actingAs($user)
            ->post(route('transporteur.trajets.store'), $this->donneesTrajet($vehicule->id))
            ->assertRedirect(route('transporteur.trajets.index'));

        $this->assertSame(Trajet::STATUT_PUBLIE, Trajet::first()->statut);
    }

    public function test_un_trajet_ne_peut_pas_utiliser_un_vehicule_en_maintenance(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        $vehicule = Vehicule::factory()->create([
            'transporteur_id' => $transporteur->id,
            'statut' => Vehicule::STATUT_MAINTENANCE,
        ]);

        $this->actingAs($user)
            ->post(route('transporteur.trajets.store'), $this->donneesTrajet($vehicule->id))
            ->assertSessionHasErrors('vehicule_id');

        $this->assertSame(0, Trajet::count());
    }

    public function test_un_vehicule_avec_des_departs_programmes_ne_passe_pas_en_maintenance(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        $vehicule = Vehicule::factory()->create(['transporteur_id' => $transporteur->id, 'capacite' => 18]);

        Trajet::factory()->create([
            'transporteur_id' => $transporteur->id,
            'vehicule_id' => $vehicule->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'statut' => Trajet::STATUT_PUBLIE,
        ]);

        $this->actingAs($user)
            ->put(route('transporteur.vehicules.update', $vehicule), [
                'immatriculation' => $vehicule->immatriculation,
                'marque' => $vehicule->marque,
                'type_vehicule' => $vehicule->type_vehicule,
                'capacite' => 18,
                'statut' => Vehicule::STATUT_MAINTENANCE,
                'note_maintenance' => 'Révision des freins',
            ])
            ->assertSessionHasErrors('statut');

        $this->assertSame(Vehicule::STATUT_ACTIF, $vehicule->fresh()->statut);
    }

    public function test_un_vehicule_sans_depart_peut_passer_en_maintenance(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        $vehicule = Vehicule::factory()->create(['transporteur_id' => $transporteur->id, 'capacite' => 18]);

        $this->actingAs($user)
            ->put(route('transporteur.vehicules.update', $vehicule), [
                'immatriculation' => $vehicule->immatriculation,
                'marque' => $vehicule->marque,
                'type_vehicule' => $vehicule->type_vehicule,
                'capacite' => 18,
                'statut' => Vehicule::STATUT_MAINTENANCE,
                'note_maintenance' => 'Révision des freins',
            ])
            ->assertRedirect(route('transporteur.vehicules.index'));

        $this->assertSame(Vehicule::STATUT_MAINTENANCE, $vehicule->fresh()->statut);
    }

    public function test_ajout_d_un_vehicule_genere_ses_sieges(): void
    {
        [$user] = $this->transporteurValide();

        $this->actingAs($user)
            ->post(route('transporteur.vehicules.store'), [
                'immatriculation' => 'dk-9999-zz',
                'marque' => 'Toyota',
                'type_vehicule' => 'minibus',
                'capacite' => 20,
                'statut' => 'actif',
            ])
            ->assertRedirect(route('transporteur.vehicules.index'));

        $vehicule = Vehicule::where('immatriculation', 'DK-9999-ZZ')->first();

        $this->assertNotNull($vehicule);
        $this->assertSame(20, $vehicule->sieges()->count());
    }

    public function test_la_liste_des_vehicules_s_affiche_avec_filtres(): void
    {
        [$user, $transporteur] = $this->transporteurValide();
        Vehicule::factory()->deType('car')->create(['transporteur_id' => $transporteur->id]);

        $this->actingAs($user)->get(route('transporteur.vehicules.index'))->assertOk();
        $this->actingAs($user)->get(route('transporteur.vehicules.index', ['type' => 'car', 'statut' => 'actif']))->assertOk();
    }

    public function test_un_autre_transporteur_ne_voit_pas_le_detail_d_un_trajet(): void
    {
        $trajet = $this->creerTrajetPublie();
        [$autre] = $this->transporteurValide();

        $this->actingAs($autre)->get(route('transporteur.trajets.show', $trajet))->assertForbidden();
    }

    public function test_le_detail_d_un_trajet_liste_les_passagers(): void
    {
        $trajet = $this->creerTrajetPublie();
        $voyageur = User::factory()->voyageur()->create(['name' => 'Awa Diouf']);

        app(ReservationService::class)->reserver($voyageur, $trajet, [$trajet->siegesLibres()->first()->id]);

        $this->actingAs($trajet->transporteur->user)
            ->get(route('transporteur.trajets.show', $trajet))
            ->assertOk()
            ->assertSee('Awa Diouf');
    }
}