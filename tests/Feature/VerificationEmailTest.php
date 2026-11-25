<?php

namespace Tests\Feature;

use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_l_inscription_envoie_le_lien_de_verification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'type_compte' => 'voyageur',
            'prenom' => 'Fatou',
            'nom' => 'Sow',
            'email' => 'fatou.sow@exemple.sn',
            'telephone' => '77 482 19 80',
            'password' => 'motdepasse1',
            'accepte_cgu' => '1',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'fatou.sow@exemple.sn')->first();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_un_compte_non_verifie_est_renvoye_vers_la_page_de_verification(): void
    {
        $voyageur = User::factory()->unverified()->voyageur()->create();

        $this->actingAs($voyageur)->get(route('voyageur.dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($voyageur)->get(route('verification.notice'))->assertOk()->assertSee($voyageur->email);
    }

    public function test_un_transporteur_non_verifie_est_bloque(): void
    {
        $user = User::factory()->unverified()->transporteur()->create();
        Transporteur::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('transporteur.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_le_lien_signe_verifie_l_adresse(): void
    {
        $user = User::factory()->unverified()->voyageur()->create();
        Event::fake([Verified::class]);

        $lien = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($lien)->assertRedirect(route('dashboard', absolute: false) . '?verified=1');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_un_lien_avec_un_mauvais_hash_est_refuse(): void
    {
        $user = User::factory()->unverified()->voyageur()->create();

        $lien = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('autre@exemple.sn'),
        ]);

        $this->actingAs($user)->get($lien);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_un_lien_non_signe_est_refuse(): void
    {
        $user = User::factory()->unverified()->voyageur()->create();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_le_lien_peut_etre_renvoye(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->voyageur()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_un_voyageur_verifie_accede_a_son_espace(): void
    {
        $voyageur = User::factory()->voyageur()->create();

        $this->actingAs($voyageur)->get(route('voyageur.dashboard'))->assertOk();
    }

    public function test_un_transporteur_cree_par_l_admin_est_considere_comme_verifie(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.transporteurs.store'), [
            'type' => 'compagnie',
            'nom_responsable' => 'Amadou Fall',
            'nom_compagnie' => 'Baol Express',
            'email' => 'direction@baolexpress.sn',
            'telephone' => '77 450 11 90',
            'numero_agrement' => 'AGR-100492',
            'password' => 'MotDePasse2026!',
            'valider_immediatement' => '1',
        ]);

        $this->assertTrue(User::where('email', 'direction@baolexpress.sn')->first()->hasVerifiedEmail());
    }
}