<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\StoreUnavailableException;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderStatusUpdated;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Enregistre une commande. Les prix et le total sont recalculés à partir
     * de la base de données : aucune donnée tarifaire du navigateur n'est utilisée.
     *
     * @param  array<int|string, int|string>  $items  product_id => quantité
     * @param  array{name: string, phone: string, area: string, address: string, notes?: ?string}  $customer
     */
    public function place(Store $store, array $items, array $customer, string $channel = 'web', bool $isDemo = false): Order
    {
        $this->ensureStoreAcceptsOrders($store);

        $items = collect($items)
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->filter(fn ($qty, $id) => $id > 0 && $qty > 0);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Votre panier est vide.']);
        }

        if ($items->contains(fn ($qty) => $qty > CartService::MAX_QUANTITY)) {
            throw ValidationException::withMessages(['cart' => 'Quantité trop élevée pour un produit.']);
        }

        $order = DB::transaction(function () use ($store, $items, $customer, $channel, $isDemo) {
            // Verrouille la boutique et les produits pour éviter les incohérences de stock.
            $store = Store::query()->lockForUpdate()->findOrFail($store->id);
            $this->ensureStoreAcceptsOrders($store);

            $products = Product::query()
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->whereIn('id', $items->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotal = '0.00';

            foreach ($items as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages(['cart' => 'Un produit de votre panier n\'est plus disponible.']);
                }

                if ($product->tracksStock() && $quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuffisant pour « {$product->name} » ({$product->stock_quantity} disponible(s)).",
                    ]);
                }

                $unitPrice = $product->effectivePrice();
                $lineTotal = Money::multiply($unitPrice, $quantity);
                $subtotal = Money::add($subtotal, $lineTotal);
                $lines[] = compact('product', 'quantity', 'unitPrice', 'lineTotal');
            }

            // Frais de livraison recalculés côté serveur à partir de la zone choisie.
            $delivery = $this->deliveryQuote($store, $customer['delivery_zone_id'] ?? null, $subtotal);

            $client = Customer::query()->firstOrNew(['store_id' => $store->id, 'phone' => $customer['phone']]);
            $client->fill([
                'name' => $customer['name'],
                'area' => $customer['area'],
                'address' => $customer['address'],
            ]);
            $client->store_id = $store->id;
            $client->save();

            $order = new Order;
            $order->forceFill([
                'store_id' => $store->id,
                'customer_id' => $client->id,
                'reference' => $this->generateReference(),
                'status' => OrderStatus::New,
                'channel' => $channel === 'whatsapp' ? 'whatsapp' : 'web',
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'customer_area' => $customer['area'],
                'delivery_address' => $customer['address'],
                'delivery_zone_id' => $delivery['zone']?->id,
                'delivery_zone_name' => $delivery['zone']?->name,
                'customer_notes' => $customer['notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $delivery['fee'],
                'total' => Money::add($subtotal, $delivery['fee']),
                'is_demo' => $isDemo,
            ])->save();

            foreach ($lines as $line) {
                $item = new OrderItem;
                $item->forceFill([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'unit_price' => $line['unitPrice'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['lineTotal'],
                ])->save();

                if ($line['product']->tracksStock()) {
                    $line['product']->decrement('stock_quantity', $line['quantity']);
                }
            }

            $this->recordHistory($order, null, OrderStatus::New, 'Commande passée par le client', null);

            return $order;
        });

        Notifier::storeMembers($store, new NewOrderReceived($order), ability: 'orders');

        return $order;
    }

    /**
     * Change le statut d'une commande en respectant les transitions autorisées.
     */
    public function changeStatus(Order $order, OrderStatus $target, ?string $note, User $by): Order
    {
        DB::transaction(function () use (&$order, $target, $note, $by) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (! $from->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => "Impossible de passer de « {$from->label()} » à « {$target->label()} ».",
                ]);
            }

            $order->status = $target;
            $order->save();

            if ($target === OrderStatus::Cancelled) {
                $this->restoreStock($order);
            }

            $this->recordHistory($order, $from, $target, $note, $by);
        });

        Notifier::storeMembers($order->store, new OrderStatusUpdated($order), $by, 'orders');

        return $order;
    }

    /**
     * Zone et frais de livraison pour un sous-total donné.
     * Si la boutique a des zones actives, le choix d'une zone est obligatoire.
     *
     * @return array{zone: ?DeliveryZone, fee: string, free: bool}
     */
    public function deliveryQuote(Store $store, int|string|null $zoneId, string $subtotal): array
    {
        $zones = $store->deliveryZones()->where('is_active', true);

        if (! (clone $zones)->exists()) {
            return ['zone' => null, 'fee' => '0.00', 'free' => false];
        }

        $zone = $zoneId ? (clone $zones)->whereKey((int) $zoneId)->first() : null;

        if (! $zone) {
            throw ValidationException::withMessages(['delivery_zone_id' => 'Choisissez une zone de livraison valide.']);
        }

        $threshold = $store->free_delivery_threshold;
        $free = $threshold !== null && bccomp($subtotal, (string) $threshold, 2) >= 0;

        return ['zone' => $zone, 'fee' => $free ? '0.00' : Money::normalize($zone->fee), 'free' => $free && bccomp((string) $zone->fee, '0', 2) > 0];
    }

    public function ensureStoreAcceptsOrders(Store $store): void
    {
        if (! $store->isOperational() || ! $store->is_published) {
            throw new StoreUnavailableException($store);
        }
    }

    /** Message WhatsApp construit uniquement à partir des données enregistrées. */
    public function whatsappMessage(Order $order): string
    {
        $order->loadMissing('items', 'store');
        $lines = [
            "Bonjour {$order->store->name}, voici ma commande *{$order->reference}* :",
            '',
        ];

        foreach ($order->items as $item) {
            $lines[] = "• {$item->quantity} × {$item->product_name} — ".Money::format($item->line_total);
        }

        $lines[] = '';
        if ($order->delivery_zone_name) {
            $lines[] = 'Sous-total : '.Money::format($order->subtotal);
            $lines[] = "Livraison ({$order->delivery_zone_name}) : ".(bccomp((string) $order->delivery_fee, '0', 2) > 0 ? Money::format($order->delivery_fee) : 'offerte');
        }
        $lines[] = '*Total : '.Money::format($order->total).'*';
        $lines[] = '';
        $lines[] = "Nom : {$order->customer_name}";
        $lines[] = "Téléphone : {$order->customer_phone}";
        $lines[] = "Quartier : {$order->customer_area}";
        $lines[] = "Livraison : {$order->delivery_address}";

        if ($order->customer_notes) {
            $lines[] = "Notes : {$order->customer_notes}";
        }

        return implode("\n", $lines);
    }

    public function whatsappUrl(Order $order): ?string
    {
        $number = $order->store->whatsappNumber();

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode($this->whatsappMessage($order)) : null;
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            $product = Product::withTrashed()->where('store_id', $order->store_id)->find($item->product_id);

            if ($product && $product->tracksStock()) {
                $product->increment('stock_quantity', $item->quantity);
            }
        }
    }

    private function recordHistory(Order $order, ?OrderStatus $from, OrderStatus $to, ?string $note, ?User $by): void
    {
        $history = new OrderStatusHistory;
        $history->forceFill([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'user_id' => $by?->id,
        ])->save();
    }

    private function generateReference(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = 'CMD-'.$code;
        } while (Order::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
