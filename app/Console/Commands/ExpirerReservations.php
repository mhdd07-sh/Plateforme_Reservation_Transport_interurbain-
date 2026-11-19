<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;

class ExpirerReservations extends Command
{
    protected $signature = 'reservations:expirer';

    protected $description = 'Expire les réservations en attente dont le délai de paiement est dépassé et libère leurs sièges';

    public function handle(): int
    {
        $total = 0;

        Reservation::enAttenteExpirees()->each(function (Reservation $reservation) use (&$total) {
            $reservation->expirer();
            $total++;
        });

        $this->info($total . ' réservation(s) expirée(s).');

        return self::SUCCESS;
    }
}