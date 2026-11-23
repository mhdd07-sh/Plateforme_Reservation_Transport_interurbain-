<?php

use App\Http\Controllers\Voyageur\DashboardController;
use App\Http\Controllers\Voyageur\PaiementController;
use App\Http\Controllers\Voyageur\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:voyageur'])
    ->prefix('voyageur')
    ->name('voyageur.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');

        Route::post('trajets/{trajet}/reserver', [ReservationController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('reservations.store');

        Route::post('reservations/{reservation}/abandonner', [ReservationController::class, 'abandonner'])
            ->name('reservations.abandonner');

        // Paiement (étape « Paiement » puis saisie du code)
        Route::get('reservations/{reservation}/paiement', [PaiementController::class, 'create'])->name('paiement.create');
        Route::post('reservations/{reservation}/paiement', [PaiementController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('paiement.store');
        Route::get('paiements/{paiement}/confirmation', [PaiementController::class, 'code'])->name('paiement.code');
        Route::post('paiements/{paiement}/confirmation', [PaiementController::class, 'confirmer'])
            ->middleware('throttle:10,1')
            ->name('paiement.confirmer');
    });