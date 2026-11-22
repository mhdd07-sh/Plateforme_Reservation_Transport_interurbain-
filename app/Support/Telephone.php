<?php

namespace App\Support;

class Telephone
{
    /** Garde uniquement les 9 chiffres d'un numéro sénégalais (retire espaces, +221, 00221). */
    public static function normaliser(string $numero): string
    {
        $chiffres = preg_replace('/\D/', '', $numero);

        if (str_starts_with($chiffres, '00221')) {
            $chiffres = substr($chiffres, 5);
        } elseif (str_starts_with($chiffres, '221') && strlen($chiffres) > 9) {
            $chiffres = substr($chiffres, 3);
        }

        return $chiffres;
    }
}