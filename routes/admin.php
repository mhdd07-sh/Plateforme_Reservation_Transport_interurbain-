<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TrajetController;
use App\Http\Controllers\Admin\TransporteurController;
use App\Http\Controllers\Admin\UtilisateurController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Transporteurs : liste, création, détail et changements de statut
        Route::resource('transporteurs', TransporteurController::class)
            ->only(['index', 'create', 'store', 'show']);
        Route::post('transporteurs/{transporteur}/valider', [TransporteurController::class, 'valider'])->name('transporteurs.valider');
        Route::post('transporteurs/{transporteur}/refuser', [TransporteurController::class, 'refuser'])->name('transporteurs.refuser');
        Route::post('transporteurs/{transporteur}/suspendre', [TransporteurController::class, 'suspendre'])->name('transporteurs.suspendre');
        Route::post('transporteurs/{transporteur}/reactiver', [TransporteurController::class, 'reactiver'])->name('transporteurs.reactiver');

        // Utilisateurs
        Route::get('utilisateurs', [UtilisateurController::class, 'index'])->name('utilisateurs.index');
        Route::post('utilisateurs/{user}/desactiver', [UtilisateurController::class, 'desactiver'])->name('utilisateurs.desactiver');
        Route::post('utilisateurs/{user}/reactiver', [UtilisateurController::class, 'reactiver'])->name('utilisateurs.reactiver');

        // Supervision des trajets
        Route::get('trajets', [TrajetController::class, 'index'])->name('trajets.index');
        Route::get('trajets/{trajet}', [TrajetController::class, 'show'])->name('trajets.show');
        Route::post('trajets/{trajet}/retirer', [TrajetController::class, 'retirer'])->name('trajets.retirer');

                Route::post('trajets/{trajet}/annuler', [TrajetController::class, 'annuler'])->name('trajets.annuler');
    });