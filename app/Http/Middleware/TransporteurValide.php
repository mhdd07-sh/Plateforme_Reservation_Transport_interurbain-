<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route aux transporteurs dont le compte a été validé par l'administrateur.
 * À placer APRÈS le middleware 'role:transporteur'.
 */
class TransporteurValide
{
    public function handle(Request $request, Closure $next): Response
    {
        $transporteur = $request->user()?->transporteur;

        if (! $transporteur || ! $transporteur->estValide()) {
            return redirect()
                ->route('transporteur.dashboard')
                ->with('erreur', "Votre compte transporteur doit être validé par l'administrateur pour accéder à cette section.");
        }

        return $next($request);
    }
}