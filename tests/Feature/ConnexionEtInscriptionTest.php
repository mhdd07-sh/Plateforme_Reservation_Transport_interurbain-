<?php

namespace Tests\Feature;

use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnexionEtInscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_connexion_par_email(): void
    {
        $user = User::factory()->create(['email' => 'fatou@exemple.sn']);

        $this->post('/login', ['identifiant' => 'fatou@exemple.sn', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_connexion_par_telephone_avec_ou_sans_indicatif(): void
    {
        $user = User::factory()->create(['telephone' => '771234567']);

        $this->post('/login', ['identifiant' => '77 123 45 67', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['identifiant' => '+221 77 123 45 67', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_mauvais_mot_de_passe_refuse(): void
    {
        User::factory()->create(['email' => 'fatou@exemple.sn']);

        $this->post('/login', ['identifiant' => 'fatou@exemple.sn', 'password' => 'faux'])
            ->assertSessionHasErrors('identifiant');

        $this->assertGuest();
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        User::factory()->create(['email' => 'fatou@exemple.sn', 'actif' => false]);

        $this->post('/login', ['identifiant' => 'fatou@exemple.sn', 'password' => 'password'])
            ->assertSessionHasErrors('identifiant');

        $this->assertGuest();
    }

    private function donneesInscription(array $surcharge = []): array
    {
        return array_merge([
            'type_compte' => 'voyageur',
            'prenom' => 'Fatou',
            'nom' => 'Sow',
            'email' => 'fatou.sow@exemple.sn',
            'telephone' => '77 482 19 80',
            'password' => 'motdepasse1',
            'accepte_cgu' => '1',
        ], $surcharge);
    }

    public function test_inscription_voyageur(): void
    {
        $this->post('/register', $this->donneesInscription())
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'fatou.sow@exemple.sn')->first();

        $this->assertSame('Fatou Sow', $user->name);
        $this->assertSame('774821980', $user->telephone);
        $this->assertSame(User::ROLE_VOYAGEUR, $user->role);
        $this->assertNull($user->transporteur);
    }

    public function test_inscription_compagnie_cree_un_transporteur_en_attente(): void
    {
        $this->post('/register', $this->donneesInscription([
            'type_compte' => 'compagnie',
            'nom_compagnie' => 'Sahel Voyage',
            'numero_agrement' => 'AGR-777001',
        ]))->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'fatou.sow@exemple.sn')->first();

        $this->assertSame(User::ROLE_TRANSPORTEUR, $user->role);
        $this->assertSame(Transporteur::STATUT_EN_ATTENTE, $user->transporteur->statut);
        $this->assertSame('AGR-777001', $user->transporteur->numero_agrement);
    }

    public function test_inscription_transporteur_particulier_sans_numero_d_agrement(): void
    {
        $this->post('/register', $this->donneesInscription(['type_compte' => 'particulier']))
            ->assertRedirect(route('dashboard', absolute: false));

        $transporteur = User::where('email', 'fatou.sow@exemple.sn')->first()->transporteur;

        $this->assertSame(Transporteur::TYPE_PARTICULIER, $transporteur->type);
        $this->assertNull($transporteur->numero_agrement);
    }

    public function test_une_compagnie_doit_fournir_son_numero_d_agrement(): void
    {
        $this->post('/register', $this->donneesInscription(['type_compte' => 'compagnie', 'nom_compagnie' => 'Sahel Voyage']))
            ->assertSessionHasErrors('numero_agrement');
    }

    public function test_le_role_ne_peut_pas_etre_impose_par_le_formulaire(): void
    {
        $this->post('/register', $this->donneesInscription(['role' => 'admin']));

        $this->assertSame(User::ROLE_VOYAGEUR, User::where('email', 'fatou.sow@exemple.sn')->first()->role);
    }

    public function test_numero_de_telephone_invalide_refuse(): void
    {
        $this->post('/register', $this->donneesInscription(['telephone' => '12345']))
            ->assertSessionHasErrors('telephone');
    }

        public function test_une_compagnie_peut_s_inscrire_avec_un_numero_fixe(): void
    {
        $this->post('/register', $this->donneesInscription([
            'type_compte' => 'compagnie',
            'nom_compagnie' => 'Sahel Voyage',
            'numero_agrement' => 'AGR-777002',
            'telephone' => '33 800 12 34',
        ]))->assertRedirect(route('dashboard', absolute: false));

        $this->assertSame('338001234', User::where('email', 'fatou.sow@exemple.sn')->first()->telephone);
    }

    public function test_un_voyageur_ne_peut_pas_utiliser_un_numero_fixe(): void
    {
        $this->post('/register', $this->donneesInscription(['telephone' => '33 800 12 34']))
            ->assertSessionHasErrors('telephone');
    }

    public function test_un_transporteur_particulier_ne_peut_pas_utiliser_un_numero_fixe(): void
    {
        $this->post('/register', $this->donneesInscription(['type_compte' => 'particulier', 'telephone' => '33 800 12 34']))
            ->assertSessionHasErrors('telephone');
    }

    public function test_connexion_par_numero_fixe(): void
    {
        $user = User::factory()->create(['telephone' => '338001234']);

        $this->post('/login', ['identifiant' => '33 800 12 34', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }
}