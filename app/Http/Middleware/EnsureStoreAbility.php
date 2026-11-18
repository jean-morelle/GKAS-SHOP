<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie le droit du membre dans la boutique : `store.can:catalog`.
 * (orders, catalog, settings, stats, export, billing, team)
 */
class EnsureStoreAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $store = $request->route('store');

        abort_unless($request->user()?->canInStore($store, $ability), 403, 'Votre rôle dans cette boutique ne permet pas d\'accéder à cette page.');

        return $next($request);
    }
}
