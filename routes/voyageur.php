<?php

use App\Http\Controllers\Voyageur\ReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:voyageur'])
    ->prefix('voyageur')
    ->name('voyageur.')
    ->group(function () {
        Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');

        Route::post('trajets/{trajet}/reserver', [ReservationController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('reservations.store');

        Route::post('reservations/{reservation}/abandonner', [ReservationController::class, 'abandonner'])
            ->name('reservations.abandonner');

        // Paiement, billets, annulation avec remboursement : branches suivantes.
    });