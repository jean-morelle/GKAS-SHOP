<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function show(Store $store): View
    {
        return view('shop.cart', ['store' => $store, 'cart' => $this->cart->summary($store)]);
    }

    public function add(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY],
        ]);

        // Le produit doit appartenir à cette boutique et être actif.
        $product = $store->products()->visible()->findOrFail($data['product_id']);

        if (! $product->isAvailable()) {
            return back()->with('error', 'Ce produit est actuellement en rupture de stock.');
        }

        $this->cart->add($store, $product, (int) ($data['quantity'] ?? 1));

        return $request->boolean('buy_now')
            ? redirect()->to(shop_route('cart', $store))
            : back()->with('success', "« {$product->name} » a été ajouté au panier.");
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['integer', 'min:0', 'max:'.CartService::MAX_QUANTITY],
        ]);

        foreach ($data['quantities'] as $productId => $quantity) {
            $this->cart->update($store, (int) $productId, (int) $quantity);
        }

        return redirect()->to(shop_route('cart', $store))->with('success', 'Panier mis à jour.');
    }
}
