<?php

namespace App\Support;

class Telephone
{
    /** Préfixes des numéros mobiles (9 chiffres). Seuls ces numéros peuvent recevoir Wave ou Orange Money. */
    public const PREFIXES_MOBILE = ['70', '75', '76', '77', '78'];

    /** Préfixes des lignes fixes acceptées pour les compagnies. Ajoutez-en ici si besoin (ex. '34'). */
    public const PREFIXES_FIXE = ['33'];

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

    /** 771234567 => « 77 123 45 67 » (même mise en forme pour un fixe : 33 800 00 00) */
    public static function formater(?string $numero): string
    {
        if (! $numero || strlen($numero) !== 9) {
            return (string) $numero;
        }

        return substr($numero, 0, 2) . ' ' . substr($numero, 2, 3) . ' ' . substr($numero, 5, 2) . ' ' . substr($numero, 7, 2);
    }

    /**
     * Expression régulière (avec délimiteurs) d'un numéro à 9 chiffres.
     * Par défaut : mobiles seulement ; avec $accepterFixe : mobiles et lignes fixes.
     * À utiliser en règle de validation sous forme de tableau : ['regex:' . Telephone::regex()].
     */
    public static function regex(bool $accepterFixe = false): string
    {
        $prefixes = $accepterFixe
            ? [...self::PREFIXES_MOBILE, ...self::PREFIXES_FIXE]
            : self::PREFIXES_MOBILE;

        return '/^(' . implode('|', $prefixes) . ')[0-9]{7}$/';
    }

    public static function estFixe(string $numero): bool
    {
        return in_array(substr($numero, 0, 2), self::PREFIXES_FIXE, true);
    }
}