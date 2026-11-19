<?php

namespace Database\Seeders;

use App\Models\Transporteur;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comptes de démonstration. Mot de passe de tous les comptes : password
 */
class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        // Administrateur
        User::factory()->admin()->create([
            'name' => 'Administrateur Plateforme',
            'email' => 'admin@transport.test',
            'telephone' => '770000000',
        ]);

        // Transporteurs : deux validés, un en attente de validation (pour tester l'espace admin)
        $transporteurs = [
            ['Mamadou Ndiaye', 'transporteur1@transport.test', '771100001', 'Ndiaye Transport', 'AGR-100001', Transporteur::STATUT_VALIDE],
            ['Awa Fall', 'transporteur2@transport.test', '771100002', 'Fall Express', 'AGR-100002', Transporteur::STATUT_VALIDE],
            ['Moussa Gueye', 'transporteur3@transport.test', '771100003', 'Gueye Voyages', 'AGR-100003', Transporteur::STATUT_EN_ATTENTE],
        ];

        foreach ($transporteurs as [$nom, $email, $telephone, $compagnie, $agrement, $statut]) {
            $user = User::factory()->transporteur()->create([
                'name' => $nom,
                'email' => $email,
                'telephone' => $telephone,
            ]);

            Transporteur::factory()->create([
                'user_id' => $user->id,
                'nom_compagnie' => $compagnie,
                'numero_agrement' => $agrement,
                'statut' => $statut,
            ]);
        }

        // Voyageurs
        $voyageurs = [
            ['Cheikh Diop', 'voyageur1@transport.test', '772200001'],
            ['Fatou Sarr', 'voyageur2@transport.test', '772200002'],
            ['Ibrahima Ba', 'voyageur3@transport.test', '772200003'],
        ];

        foreach ($voyageurs as [$nom, $email, $telephone]) {
            User::factory()->voyageur()->create([
                'name' => $nom,
                'email' => $email,
                'telephone' => $telephone,
            ]);
        }
    }
}