<?php

namespace Tests\Feature;

use App\Exceptions\StoreUnavailableException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Notifications\NewOrderReceived;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private array $customer = [
        'name' => 'Ama Koffi', 'phone' => '+228 90 11 22 33', 'area' => 'Bè', 'address' => 'Près du marché', 'notes' => 'Taille M',
    ];

    public function test_customer_can_order_without_account_through_the_full_flow(): void
    {
        Notification::fake();
        [$vendor, $store] = $this->vendorWithStore();
        $product = Product::factory()->for($store)->stock(10)->create(['price' => 8000]);

        $this->post("/{$store->slug}/panier", ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();
        $this->get("/{$store->slug}/panier")->assertOk()->assertSee('16 000 FCFA');
        $this->post("/{$store->slug}/commander", $this->customer)->assertRedirect("/{$store->slug}/commander/recapitulatif");
        $this->get("/{$store->slug}/commander/recapitulatif")->assertOk()->assertSee('Ama Koffi')->assertSee('16 000 FCFA');

        $response = $this->post("/{$store->slug}/commander/valider", ['channel' => 'web']);

        $order = Order::firstOrFail();
        $response->assertRedirect("/{$store->slug}/commande/{$order->reference}");
        $this->assertSame('16000.00', (string) $order->total);
        $this->assertSame('new', $order->status->value);
        $this->assertSame('Ama Koffi', $order->customer_name);
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('customers', ['store_id' => $store->id, 'phone' => '+228 90 11 22 33']);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'new']);
        Notification::assertSentTo($vendor, NewOrderReceived::class);

        $this->get("/{$store->slug}/commande/{$order->reference}")->assertOk()->assertSee($order->reference);
        $this->assertSame(0, app(\App\Services\CartService::class)->count($store));
    }

    public function test_order_reference_is_unique_and_not_guessable(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create();
        $service = app(OrderService::class);

        $a = $service->place($store, [$product->id => 1], $this->customer);
        $b = $service->place($store, [$product->id => 1], $this->customer);

        $this->assertMatchesRegularExpression('/^CMD-[A-HJ-NP-Z2-9]{10}$/', $a->reference);
        $this->assertNotSame($a->reference, $b->reference);
    }

    public function test_confirmation_page_is_private_to_the_ordering_browser(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create();
        $order = app(OrderService::class)->place($store, [$product->id => 1], $this->customer);

        $this->get("/{$store->slug}/commande/{$order->reference}")->assertNotFound();
    }

    public function test_prices_and_total_are_recalculated_server_side(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->onSale(7500)->create(['price' => 10000]);

        // Le navigateur tente d'imposer un prix et un total : ils doivent être ignorés.
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id, 'quantity' => 3, 'price' => 1, 'unit_price' => 1]);
        $this->post("/{$store->slug}/commander", $this->customer);
        $this->post("/{$store->slug}/commander/valider", ['channel' => 'web', 'total' => 1, 'subtotal' => 1, 'price' => 1]);

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('22500.00', (string) $order->total);
        $this->assertSame('7500.00', (string) $order->items->first()->unit_price);
        $this->assertSame('22500.00', (string) $order->items->first()->line_total);
    }

    public function test_order_keeps_a_snapshot_of_product_data(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create(['name' => 'Ancien nom', 'price' => 5000]);
        $order = app(OrderService::class)->place($store, [$product->id => 2], $this->customer);

        $product->update(['name' => 'Nouveau nom', 'price' => 9000]);
        $product->delete();

        $item = $order->items()->first();
        $this->assertSame('Ancien nom', $item->product_name);
        $this->assertSame('5000.00', (string) $item->unit_price);
        $this->assertSame('10000.00', (string) $order->fresh()->total);
    }

    public function test_insufficient_stock_and_inactive_products_are_refused(): void
    {
        $store = Store::factory()->create();
        $limited = Product::factory()->for($store)->stock(1)->create();
        $inactive = Product::factory()->for($store)->inactive()->create();
        $service = app(OrderService::class);

        try {
            $service->place($store, [$limited->id => 2], $this->customer);
            $this->fail('Stock insuffisant non détecté');
        } catch (ValidationException) {
        }

        try {
            $service->place($store, [$inactive->id => 1], $this->customer);
            $this->fail('Produit inactif accepté');
        } catch (ValidationException) {
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $limited->fresh()->stock_quantity);
    }

    public function test_product_from_another_store_cannot_be_ordered(): void
    {
        $store = Store::factory()->create();
        $foreign = Product::factory()->create();

        $this->expectException(ValidationException::class);
        app(OrderService::class)->place($store, [$foreign->id => 1], $this->customer);
    }

    public function test_expired_store_refuses_new_orders(): void
    {
        $store = Store::factory()->expired()->create();
        $product = Product::factory()->for($store)->create();

        $this->expectException(StoreUnavailableException::class);
        app(OrderService::class)->place($store, [$product->id => 1], $this->customer);
    }

    public function test_expired_store_refuses_orders_over_http(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create();
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id]);
        $this->post("/{$store->slug}/commander", $this->customer);

        // L'essai expire entre-temps.
        $store->forceFill(['trial_ends_at' => now()->subMinute()])->save();

        $this->post("/{$store->slug}/commander/valider", ['channel' => 'web'])->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_suspended_store_refuses_orders_even_with_active_subscription(): void
    {
        $store = Store::factory()->subscribed()->suspended()->create();
        $product = Product::factory()->for($store)->create();

        $this->expectException(StoreUnavailableException::class);
        app(OrderService::class)->place($store, [$product->id => 1], $this->customer);
    }

    public function test_whatsapp_order_is_saved_before_redirecting_with_server_side_message(): void
    {
        $store = Store::factory()->create(['whatsapp' => '+228 90 00 00 99']);
        $product = Product::factory()->for($store)->create(['name' => 'Sac kente', 'price' => 12000]);
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id]);
        $this->post("/{$store->slug}/commander", $this->customer);

        $this->post("/{$store->slug}/commander/valider", ['channel' => 'whatsapp'])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('whatsapp', $order->channel);

        $url = app(OrderService::class)->whatsappUrl($order);
        $this->assertStringStartsWith('https://wa.me/22890000099?text=', $url);
        $message = rawurldecode(substr($url, strpos($url, '=') + 1));
        $this->assertStringContainsString($order->reference, $message);
        $this->assertStringContainsString('Sac kente', $message);
        $this->assertStringContainsString('12 000 FCFA', $message);

        $this->get("/{$store->slug}/commande/{$order->reference}")->assertSee('wa.me/22890000099', false);
    }

    public function test_checkout_form_is_validated(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create();
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id]);

        $this->post("/{$store->slug}/commander", ['name' => '', 'phone' => 'abc', 'area' => '', 'address' => ''])
            ->assertSessionHasErrors(['name', 'phone', 'area', 'address']);
    }
}
