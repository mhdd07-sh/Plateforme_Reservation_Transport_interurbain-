<?php

namespace App\Exceptions;

use RuntimeException;

/** Erreur métier : l'annulation demandée ne peut pas être effectuée (message affichable). */
class AnnulationImpossibleException extends RuntimeException
{
}