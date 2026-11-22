<?php

return [

    /*
    | Fournisseur de paiement utilisé par l'application.
    | "fake" : simulation (développement et démonstration).
    | Plus tard : "wave", "orange_money"... en ajoutant une classe qui implémente
    | PaiementServiceInterface et en la déclarant dans AppServiceProvider.
    */
    'driver' => env('PAIEMENT_DRIVER', 'fake'),

    // Règles de la simulation.
    'fake' => [
        // Code de confirmation accepté (tout autre code échoue).
        'code_valide' => '123456',
        // Un numéro se terminant par ces chiffres simule un solde insuffisant.
        'suffixe_solde_insuffisant' => '00',
    ],

];