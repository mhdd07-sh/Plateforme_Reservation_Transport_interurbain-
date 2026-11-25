<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Outil de développement : avec MAIL_MAILER=log, les e-mails sont écrits dans le journal, en texte
 * encodé (quoted-printable) où les liens sont coupés. Cette commande les décode et les affiche.
 */
class AfficherDernierLien extends Command
{
    protected $signature = 'mail:lien {--n=1 : nombre de liens à afficher}';

    protected $description = "Affiche le dernier lien d'e-mail (vérification, mot de passe oublié) écrit dans storage/logs/laravel.log";

    public function handle(): int
    {
        $fichier = storage_path('logs/laravel.log');

        if (! is_file($fichier)) {
            $this->error("Aucun fichier de log trouvé. Vérifiez MAIL_MAILER=log dans .env.");

            return self::FAILURE;
        }

        // On ne lit que la fin du fichier (200 Ko).
        $taille = filesize($fichier);
        $poignee = fopen($fichier, 'rb');
        fseek($poignee, max(0, $taille - 204800));
        $contenu = (string) stream_get_contents($poignee);
        fclose($poignee);

        $decode = html_entity_decode(quoted_printable_decode($contenu));

        preg_match_all('~https?://[^\s"<>\]\)]+/(?:verify-email|reset-password)/[^\s"<>\]\)]+~', $decode, $trouves);

        $liens = array_values(array_unique($trouves[0]));

        if ($liens === []) {
            $this->warn("Aucun lien trouvé : demandez d'abord l'e-mail (inscription, renvoi du lien, mot de passe oublié).");

            return self::SUCCESS;
        }

        foreach (array_slice($liens, -max(1, (int) $this->option('n'))) as $lien) {
            $this->line($lien);
        }

        return self::SUCCESS;
    }
}