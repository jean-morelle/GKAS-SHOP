<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boutique publique : archivée → 404 ; non publiée → visible seulement
 * par ses membres (prévisualisation) ; expirée ou suspendue → page d'indisponibilité.
 */
class EnsureShopAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Store $store */
        $store = $request->route('store');

        if ($store->status === StoreStatus::Archived) {
            abort(404);
        }

        // Les membres de la boutique et l'équipe GKAS (admin, commercial) peuvent prévisualiser une boutique non publiée.
        $user = $request->user();
        $canPreview = $user && ($user->isStaff() || $user->belongsToStore($store));
        $request->attributes->set('shop_preview', $canPreview && ! $store->is_published);

        if (! $store->is_published && ! $canPreview) {
            return response()->view('shop.unavailable', ['store' => $store, 'reason' => 'unpublished'], 404);
        }

        if (! $store->isOperational()) {
            return response()->view('shop.unavailable', ['store' => $store, 'reason' => 'expired'], 403);
        }

        return $next($request);
    }
}
