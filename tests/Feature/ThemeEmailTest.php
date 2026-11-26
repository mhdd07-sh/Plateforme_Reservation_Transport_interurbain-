<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_e_mail_de_verification_utilise_le_theme_et_le_francais(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Fatou Sow']);

        $html = strtolower((string) (new VerifyEmail)->toMail($user)->render());

        $this->assertStringContainsString('confirmer mon adresse e-mail', $html);
        $this->assertStringContainsString('0d5c3a', $html);
        $this->assertStringNotContainsString('all rights reserved', $html);
        $this->assertStringNotContainsString("you're having trouble", $html);
    }

    public function test_l_e_mail_de_mot_de_passe_oublie_utilise_le_theme(): void
    {
        $user = User::factory()->create();

        $html = strtolower((string) (new ResetPassword('jeton'))->toMail($user)->render());

        $this->assertStringContainsString('choisir un nouveau mot de passe', $html);
        $this->assertStringContainsString('0d5c3a', $html);
    }
}