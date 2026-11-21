<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à un ou plusieurs rôles.
 * Usage : ->middleware('role:admin')  ou  ->middleware('role:admin,transporteur')
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Un compte désactivé par l'administrateur est déconnecté immédiatement.
        if (! $user->actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => "Votre compte a été désactivé. Contactez l'administrateur.",
            ]);
        }

        if (! $user->aRole(...$roles)) {
            abort(403, 'Vous n\'avez pas accès à cette section.');
        }

        return $next($request);
    }
}