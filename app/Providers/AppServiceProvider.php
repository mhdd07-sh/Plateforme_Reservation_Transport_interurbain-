<?php

namespace App\Providers;

use App\Services\Paiement\FakePaiementService;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Le fournisseur de paiement se choisit dans .env : PAIEMENT_DRIVER=fake
        $this->app->bind(PaiementServiceInterface::class, function () {
            return match (config('paiement.driver')) {
                'fake' => new FakePaiementService(),
                default => throw new InvalidArgumentException(
                    'Fournisseur de paiement inconnu : ' . config('paiement.driver')
                ),
            };
        });
    }

        public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->greeting('Bonjour,')
                ->line('Vous recevez ce message car une réinitialisation du mot de passe a été demandée pour votre compte ' . config('app.name') . '.')
                ->action('Choisir un nouveau mot de passe', $url)
                ->line("Ce lien expire dans {$minutes} minutes.")
                ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.")
                ->salutation("L'équipe " . config('app.name'));
        });
    }
}