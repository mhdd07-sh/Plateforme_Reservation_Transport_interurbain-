<?php

namespace Tests\Feature;

use App\Models\JournalAdmin;
use App\Models\Trajet;
use App\Models\Transporteur;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreeDonneesTransport;
use Tests\TestCase;

class EspaceAdminTest extends TestCase
{
    use RefreshDatabase;
    use CreeDonneesTransport;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Test']);
    }

    private function transporteurEnAttente(): Transporteur
    {
        return Transporteur::factory()->enAttente()->create();
    }

    public function test_le_tableau_de_bord_s_affiche(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Voyageurs inscrits');
        $this->actingAs($this->admin)->get(route('admin.dashboard', ['periode' => 7]))->assertOk();
    }

    public function test_un_voyageur_n_accede_pas_aux_pages_admin(): void
    {
        $voyageur = User::factory()->voyageur()->create();

        foreach (['admin.transporteurs.index', 'admin.utilisateurs.index', 'admin.trajets.index'] as $route) {
            $this->actingAs($voyageur)->get(route($route))->assertForbidden();
        }
    }

    public function test_la_liste_des_transporteurs_se_filtre_et_se_recherche(): void
    {
        $this->transporteurEnAttente()->update(['nom_compagnie' => 'Baol Transport']);
        Transporteur::factory()->create(['nom_compagnie' => 'Sahel Voyage']);

        $this->actingAs($this->admin)->get(route('admin.transporteurs.index', ['statut' => 'en_attente']))
            ->assertOk()->assertSee('Baol Transport')->assertDontSee('Sahel Voyage');

        $this->actingAs($this->admin)->get(route('admin.transporteurs.index', ['q' => 'Sahel']))
            ->assertOk()->assertSee('Sahel Voyage')->assertDontSee('Baol Transport');
    }

    public function test_valider_un_transporteur_en_attente_est_journalise(): void
    {
        $transporteur = $this->transporteurEnAttente();

        $this->actingAs($this->admin)->post(route('admin.transporteurs.valider', $transporteur))->assertSessionHas('succes');

        $this->assertSame(Transporteur::STATUT_VALIDE, $transporteur->fresh()->statut);
        $this->assertSame(1, JournalAdmin::where('action', 'transporteur_valide')->count());
    }

    public function test_refuser_puis_reactiver(): void
    {
        $transporteur = $this->transporteurEnAttente();

        $this->actingAs($this->admin)->post(route('admin.transporteurs.refuser', $transporteur));
        $this->assertSame(Transporteur::STATUT_REFUSE, $transporteur->fresh()->statut);

        $this->actingAs($this->admin)->post(route('admin.transporteurs.reactiver', $transporteur));
        $this->assertSame(Transporteur::STATUT_VALIDE, $transporteur->fresh()->statut);
    }

    public function test_une_transition_interdite_est_refusee(): void
    {
        $valide = Transporteur::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.transporteurs.valider', $valide))->assertSessionHas('erreur');
        $this->assertSame(0, JournalAdmin::count());
    }

    public function test_suspendre_un_transporteur_retire_ses_trajets_de_la_recherche(): void
    {
        $trajet = $this->creerTrajetPublie();
        $this->assertSame(1, Trajet::visibles()->count());

        $this->actingAs($this->admin)->post(route('admin.transporteurs.suspendre', $trajet->transporteur));

        $this->assertSame(0, Trajet::visibles()->count());
    }

    public function test_creation_d_un_transporteur_par_l_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.transporteurs.store'), [
                'type' => 'compagnie',
                'nom_responsable' => 'Amadou Fall',
                'nom_compagnie' => 'Baol Express',
                'email' => 'direction@baolexpress.sn',
                'telephone' => '77 450 11 90',
                'numero_agrement' => 'AGR-100492',
                'password' => 'MotDePasse2026!',
                'valider_immediatement' => '1',
            ])
            ->assertRedirect();

        $user = User::where('email', 'direction@baolexpress.sn')->first();

        $this->assertSame(User::ROLE_TRANSPORTEUR, $user->role);
        $this->assertSame('774501190', $user->telephone);
        $this->assertTrue(Hash::check('MotDePasse2026!', $user->password));
        $this->assertSame(Transporteur::STATUT_VALIDE, $user->transporteur->statut);
    }

    public function test_un_numero_d_agrement_existant_est_refuse(): void
    {
        Transporteur::factory()->create(['numero_agrement' => 'AGR-100492']);

        $this->actingAs($this->admin)
            ->post(route('admin.transporteurs.store'), [
                'type' => 'compagnie', 'nom_responsable' => 'X Y', 'nom_compagnie' => 'Z',
                'email' => 'z@exemple.sn', 'telephone' => '77 450 11 91',
                'numero_agrement' => 'AGR-100492', 'password' => 'MotDePasse2026!',
            ])
            ->assertSessionHasErrors('numero_agrement');
    }

    public function test_le_detail_d_un_transporteur_et_ses_onglets(): void
    {
        $transporteur = Transporteur::factory()->create();

        foreach (['vehicules', 'trajets', 'historique'] as $onglet) {
            $this->actingAs($this->admin)->get(route('admin.transporteurs.show', [$transporteur, 'onglet' => $onglet]))->assertOk();
        }
    }

    public function test_la_liste_des_utilisateurs_se_filtre_par_role(): void
    {
        User::factory()->voyageur()->create(['name' => 'Cheikh Diop']);
        User::factory()->transporteur()->create(['name' => 'Moussa Gueye']);

        $this->actingAs($this->admin)->get(route('admin.utilisateurs.index', ['role' => 'voyageur']))
            ->assertOk()->assertSee('Cheikh Diop')->assertDontSee('Moussa Gueye');
    }

    public function test_desactiver_un_utilisateur_l_empeche_de_se_connecter(): void
    {
        $voyageur = User::factory()->voyageur()->create(['email' => 'v@exemple.sn']);

        $this->actingAs($this->admin)->post(route('admin.utilisateurs.desactiver', $voyageur));

        $this->assertFalse($voyageur->fresh()->actif);
        $this->post('/logout');
        $this->post('/login', ['identifiant' => 'v@exemple.sn', 'password' => 'password'])->assertSessionHasErrors('identifiant');
    }

    public function test_l_admin_ne_peut_pas_desactiver_son_propre_compte(): void
    {
        $this->actingAs($this->admin)->post(route('admin.utilisateurs.desactiver', $this->admin))->assertSessionHas('erreur');

        $this->assertTrue($this->admin->fresh()->actif);
    }

    public function test_desactiver_un_transporteur_suspend_son_profil(): void
    {
        $transporteur = Transporteur::factory()->create();

        $this->actingAs($this->admin)->post(route('admin.utilisateurs.desactiver', $transporteur->user));

        $this->assertSame(Transporteur::STATUT_SUSPENDU, $transporteur->fresh()->statut);
    }

    public function test_la_fiche_utilisateur_s_affiche(): void
    {
        $voyageur = User::factory()->voyageur()->create(['name' => 'Awa Ndiaye']);

        $this->actingAs($this->admin)
            ->get(route('admin.utilisateurs.index', ['utilisateur' => $voyageur->id]))
            ->assertOk()->assertSee('Fiche utilisateur');
    }

    public function test_la_supervision_des_trajets_se_filtre(): void
    {
        $trajet = $this->creerTrajetPublie();
        $trajet->update(['ville_depart' => 'Bambey', 'ville_arrivee' => 'Nioro']);

        $this->actingAs($this->admin)->get(route('admin.trajets.index'))->assertOk()->assertSee('Bambey');
        $this->actingAs($this->admin)->get(route('admin.trajets.index', ['statut' => 'brouillon']))->assertOk()->assertDontSee('Bambey');
        $this->actingAs($this->admin)->get(route('admin.trajets.index', ['statut' => 'complet']))->assertOk()->assertDontSee('Bambey');
        $this->actingAs($this->admin)->get(route('admin.trajets.show', $trajet))->assertOk()->assertSee('Bambey');
    }

    public function test_retirer_de_la_vente_un_trajet_sans_reservation(): void
    {
        $trajet = $this->creerTrajetPublie();

        $this->actingAs($this->admin)->post(route('admin.trajets.retirer', $trajet))->assertSessionHas('succes');

        $this->assertSame(Trajet::STATUT_BROUILLON, $trajet->fresh()->statut);
    }

    public function test_un_trajet_avec_reservation_ne_peut_pas_etre_retire(): void
    {
        $trajet = $this->creerTrajetPublie();
        app(ReservationService::class)->reserver(User::factory()->voyageur()->create(), $trajet, [$trajet->siegesLibres()->first()->id]);

        $this->actingAs($this->admin)->post(route('admin.trajets.retirer', $trajet))->assertSessionHas('erreur');

        $this->assertSame(Trajet::STATUT_PUBLIE, $trajet->fresh()->statut);
    }
}