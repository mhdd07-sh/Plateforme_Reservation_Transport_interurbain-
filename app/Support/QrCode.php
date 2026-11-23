<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * QR code au format SVG (aucune extension PHP supplémentaire nécessaire).
     * Retourne null si le paquet bacon/bacon-qr-code n'est pas installé : la page affiche alors
     * seulement la référence du billet.
     */
    public static function svg(string $contenu, int $taille = 200): ?string
    {
        if (! class_exists(Writer::class) || ! class_exists(SvgImageBackEnd::class)) {
            return null;
        }

        $rendu = new ImageRenderer(new RendererStyle($taille, 1), new SvgImageBackEnd());
        $svg = (new Writer($rendu))->writeString($contenu);

        // Retire la déclaration XML pour pouvoir intégrer le SVG directement dans la page.
        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }
}