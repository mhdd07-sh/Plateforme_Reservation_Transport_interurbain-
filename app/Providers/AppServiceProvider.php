<?php

namespace App\Providers;

use App\Services\Paiement\FakePaiementService;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use InvalidArgumentException;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;


class AppServiceProvider extends ServiceProvider
{
     public function register(): void
    {
        // Le fournisseur de paiement se choisit dans .env :
        // PAIEMENT_DRIVER=fake
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
        // Personnalisation du contenu de l'e-mail de vérification.
// Le thème Markdown Réserv'Transport reste automatiquement utilisé.
VerifyEmail::toMailUsing(function (object $notifiable, string $url) {

    $minutes = config('auth.verification.expire', 60);

    return (new MailMessage)
        ->subject("Bienvenue sur Réserv'Transport - Vérifiez votre adresse e-mail")
        ->greeting('Bonjour ' . $notifiable->name . ' !')
        ->line("Merci de vous être inscrit sur Réserv'Transport.")
        ->line("Pour activer votre compte, veuillez confirmer votre adresse e-mail.")
        ->action('Confirmer mon adresse e-mail', $url)
        ->line("Ce lien expire dans {$minutes} minutes.")
        ->line("Si vous n'avez pas créé de compte, ignorez simplement ce message.")
        ->salutation("Cordialement, l'équipe Réserv'Transport");
});

        // E-mail de réinitialisation du mot de passe.
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {

            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = config(
                'auth.passwords.' . config('auth.defaults.passwords') . '.expire'
            );

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe')
                ->greeting('Bonjour,')
                ->line(
                    'Vous recevez ce message car une réinitialisation du mot de passe a été demandée pour votre compte Réserv\'Transport.'
                )
                ->action('Choisir un nouveau mot de passe', $url)
                ->line("Ce lien expire dans {$minutes} minutes.")
                ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message.")
                ->salutation("L'équipe Réserv'Transport");
        });
    }
}