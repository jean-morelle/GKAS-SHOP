<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\Order;
use App\Models\Store;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function create(Store $store): View|RedirectResponse
    {
        $cart = $this->cart->summary($store);

        if ($cart['lines']->isEmpty()) {
            return redirect()->to(shop_route('cart', $store));
        }

        return view('shop.checkout', [
            'store' => $store,
            'cart' => $cart,
            'customer' => session($this->sessionKey($store), []),
            'zones' => $store->deliveryZones()->available()->get(),
        ]);
    }

    /** Étape 1 : validation des coordonnées et de la zone, puis récapitulatif. */
    public function review(CheckoutRequest $request, Store $store): RedirectResponse
    {
        session([$this->sessionKey($store) => $request->validated()]);

        return redirect()->to(shop_route('checkout.summary', $store));
    }

    /** Étape 2 : récapitulatif (produits + livraison) calculé côté serveur. */
    public function summary(Store $store): View|RedirectResponse
    {
        $cart = $this->cart->summary($store);
        $customer = session($this->sessionKey($store));

        if ($cart['lines']->isEmpty()) {
            return redirect()->to(shop_route('cart', $store));
        }

        if (! $customer) {
            return redirect()->to(shop_route('checkout', $store));
        }

        try {
            $delivery = $this->orders->deliveryQuote($store, $customer['delivery_zone_id'] ?? null, $cart['total']);
        } catch (ValidationException $e) {
            // La zone choisie n'existe plus (désactivée entre-temps) : retour au formulaire.
            return redirect()->to(shop_route('checkout', $store))->withErrors($e->errors());
        }

        $total = Money::add($cart['total'], $delivery['fee']);

        return view('shop.summary', compact('store', 'cart', 'customer', 'delivery', 'total'));
    }

    /** Étape 3 : enregistrement en base, puis confirmation (et WhatsApp si choisi). */
    public function place(Request $request, Store $store): RedirectResponse
    {
        $request->validate(['channel' => ['required', 'in:web,whatsapp']]);
        $customer = session($this->sessionKey($store));

        if (! $customer) {
            return redirect()->to(shop_route('checkout', $store));
        }

        // Seuls les identifiants et quantités du panier sont transmis ; prix et frais sont relus en base.
        $order = $this->orders->place($store, $this->cart->raw($store), $customer, $request->input('channel'));

        $this->cart->clear($store);
        session()->push('placed_orders', $order->reference);

        return redirect()->to(shop_route('order.confirmation', [$store, $order]))
            ->with('open_whatsapp', $order->channel === 'whatsapp');
    }

    public function confirmation(Store $store, Order $order): View
    {
        // Seul le navigateur qui a passé la commande peut voir son récapitulatif.
        abort_unless(in_array($order->reference, session('placed_orders', []), true), 404);

        $order->load('items');

        return view('shop.confirmation', [
            'store' => $store,
            'order' => $order,
            'whatsappUrl' => $this->orders->whatsappUrl($order),
            'openWhatsapp' => (bool) session('open_whatsapp'),
        ]);
    }

    private function sessionKey(Store $store): string
    {
        return 'checkout.'.$store->id;
    }
}
