<?php

use App\Http\Controllers\Transporteur\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:transporteur'])
    ->prefix('transporteur')
    ->name('transporteur.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Sections réservées aux transporteurs dont le compte est validé
        Route::middleware('transporteur.valide')->group(function () {
            // feature/gestion-vehicules : routes des véhicules
            // feature/gestion-trajets : routes des trajets
        });
    });