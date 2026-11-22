<?php

namespace App\Exceptions;

use RuntimeException;

/** Erreur métier : la réservation demandée ne peut pas être effectuée (message affichable). */
class ReservationImpossibleException extends RuntimeException
{
}