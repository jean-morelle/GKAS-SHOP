<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Panier stocké en session côté serveur : seuls les identifiants de produits
 * et les quantités sont conservés. Les prix sont toujours relus en base.
 */
class CartService
{
    public const MAX_QUANTITY = 99;

    private function key(Store $store): string
    {
        return 'cart.'.$store->id;
    }

    /** @return array<int, int> product_id => quantité */
    public function raw(Store $store): array
    {
        return session($this->key($store), []);
    }

    public function count(Store $store): int
    {
        return array_sum($this->raw($store));
    }

    public function add(Store $store, Product $product, int $quantity): void
    {
        $cart = $this->raw($store);
        $cart[$product->id] = min(self::MAX_QUANTITY, ($cart[$product->id] ?? 0) + max(1, $quantity));
        session([$this->key($store) => $cart]);
    }

    public function update(Store $store, int $productId, int $quantity): void
    {
        $cart = $this->raw($store);

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId] = min(self::MAX_QUANTITY, $quantity);
        }

        session([$this->key($store) => $cart]);
    }

    public function clear(Store $store): void
    {
        session()->forget($this->key($store));
    }

    /**
     * Lignes du panier calculées à partir des prix en base.
     *
     * @return array{lines: Collection, total: string, count: int}
     */
    public function summary(Store $store): array
    {
        $raw = $this->raw($store);
        $products = $store->products()->visible()->with('mainImage')->whereIn('id', array_keys($raw))->get()->keyBy('id');

        $lines = collect($raw)
            ->filter(fn ($qty, $id) => $products->has($id))
            ->map(function ($qty, $id) use ($products) {
                $product = $products[$id];

                return (object) [
                    'product' => $product,
                    'quantity' => (int) $qty,
                    'unit_price' => $product->effectivePrice(),
                    'line_total' => Money::multiply($product->effectivePrice(), (int) $qty),
                    'exceeds_stock' => $product->tracksStock() && $qty > $product->stock_quantity,
                ];
            })->values();

        // Nettoie les produits devenus indisponibles.
        if ($lines->count() !== count($raw)) {
            session([$this->key($store) => $lines->mapWithKeys(fn ($l) => [$l->product->id => $l->quantity])->all()]);
        }

        $total = $lines->reduce(fn ($carry, $l) => Money::add($carry, $l->line_total), '0.00');

        return ['lines' => $lines, 'total' => $total, 'count' => $lines->sum('quantity')];
    }
}
