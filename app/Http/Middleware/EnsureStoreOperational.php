<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès limité quand l'essai / l'abonnement est expiré ou la boutique suspendue :
 * le vendeur peut consulter ses données (GET) mais pas les modifier.
 */
class EnsureStoreOperational
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');

        if ($request->isMethodSafe() || $store->isOperational()) {
            return $next($request);
        }

        $target = $request->user()->canInStore($store, 'billing')
            ? route('vendor.subscription.show', $store)
            : route('vendor.dashboard', $store);

        return redirect($target)
            ->with('error', 'Votre boutique est en accès limité ('.$store->accessState()->label().'). L\'abonnement doit être renouvelé pour modifier les données.');
    }
}
