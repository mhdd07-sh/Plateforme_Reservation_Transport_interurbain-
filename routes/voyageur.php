<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du voyageur (fichier géré par l'Étudiant B)
|--------------------------------------------------------------------------
| Les routes publiques (accueil, recherche de trajets) restent dans web.php.
| Ici : tout ce qui nécessite d'être connecté en tant que voyageur
| (mes réservations, paiement, billets, annulation...).
*/

Route::middleware(['auth', 'role:voyageur'])
    ->prefix('voyageur')
    ->name('voyageur.')
    ->group(function () {
        // B ajoute ses routes ici.
    });