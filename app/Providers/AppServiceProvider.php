<?php

namespace App\Providers;

use App\Services\Paiement\FakePaiementService;
use App\Services\Paiement\PaiementServiceInterface;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

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
        //
    }
}