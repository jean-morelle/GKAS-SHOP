<?php

use App\Models\Store;
use App\Support\Money;

if (! function_exists('fcfa')) {
    /** Formate un montant : fcfa(8000) → « 8 000 FCFA ». */
    function fcfa(string|int|float|null $amount): string
    {
        return Money::format($amount);
    }
}

if (! function_exists('shop_route')) {
    /**
     * URL d'une page de boutique publique (« home », « product », « cart »…).
     * Si le client navigue sur le domaine personnalisé de la boutique, l'URL reste
     * sur ce domaine (la session et donc le panier sont propres à chaque domaine) ;
     * sinon, on utilise l'adresse de la plateforme APP_URL/{identifiant}.
     */
    function shop_route(string $name, mixed $parameters = []): string
    {
        $params = is_array($parameters) ? $parameters : [$parameters];
        $store = $params['store'] ?? $params[0] ?? null;

        if ($store instanceof Store && $store->custom_domain && app()->bound('request')
            && strcasecmp(request()->getHost(), $store->custom_domain) === 0) {
            unset($params[0], $params['store']);

            return route('domain.shop.'.$name, ['store' => $store->custom_domain] + $params);
        }

        return route('shop.'.$name, $params);
    }
}
