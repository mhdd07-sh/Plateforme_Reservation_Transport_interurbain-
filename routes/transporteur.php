<?php

use App\Http\Controllers\Transporteur\DashboardController;
use App\Http\Controllers\Transporteur\TrajetController;
use App\Http\Controllers\Transporteur\VehiculeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:transporteur'])
    ->prefix('transporteur')
    ->name('transporteur.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Sections réservées aux transporteurs dont le compte est validé
        Route::middleware('transporteur.valide')->group(function () {
            Route::resource('vehicules', VehiculeController::class)
                ->except('show')
                ->parameters(['vehicules' => 'vehicule']);

            Route::resource('trajets', TrajetController::class)
                ->except('show')
                ->parameters(['trajets' => 'trajet']);

            Route::post('trajets/{trajet}/publier', [TrajetController::class, 'publier'])->name('trajets.publier');
            Route::post('trajets/{trajet}/annuler', [TrajetController::class, 'annuler'])->name('trajets.annuler');
        });
    });