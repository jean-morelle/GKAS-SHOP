<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnecte un compte désactivé et impose le changement d'un mot de passe temporaire.
 */
class EnsureAccountUsable
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Votre compte est désactivé. Contactez GKAS-GROUP.');
        }

        // Le changement du mot de passe temporaire n'est imposé que dans les espaces privés :
        // le site public et les boutiques (dont la prévisualisation) restent consultables.
        if ($user->must_change_password && $request->routeIs('admin.*', 'vendor.*', 'notifications.*')) {
            return redirect()->route('account.edit')->with('warning', 'Veuillez définir un nouveau mot de passe personnel avant de continuer.');
        }

        return $next($request);
    }
}
