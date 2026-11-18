<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espace vendeur : l'utilisateur doit être membre de la boutique de l'URL.
 * Une boutique d'un autre vendeur renvoie 404 (on ne révèle pas son existence).
 */
class EnsureStoreMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');
        $user = $request->user();

        if (! $store instanceof Store || ! $user || ! $user->isVendor() || ! $user->belongsToStore($store)) {
            abort(404);
        }

        // Une boutique archivée n'est plus accessible au vendeur.
        if ($store->status === \App\Enums\StoreStatus::Archived) {
            abort(403, 'Cette boutique a été archivée. Contactez GKAS-GROUP.');
        }

        return $next($request);
    }
}
