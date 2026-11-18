<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryFeeTest extends TestCase
{
    use RefreshDatabase;

    private array $customer = ['name' => 'Ama', 'phone' => '+228 90 11 22 33', 'area' => 'Bè', 'address' => 'Rue 1'];

    public function test_delivery_fee_is_added_to_the_total_server_side(): void
    {
        $store = Store::factory()->create();
        $zone = DeliveryZone::factory()->for($store)->create(['name' => 'Lomé centre', 'fee' => 1000]);
        $product = Product::factory()->for($store)->create(['price' => 8000]);

        $this->post("/{$store->slug}/panier", ['product_id' => $product->id, 'quantity' => 2]);
        $this->post("/{$store->slug}/commander", $this->customer + ['delivery_zone_id' => $zone->id, 'delivery_fee' => 0, 'total' => 1])
            ->assertRedirect("/{$store->slug}/commander/recapitulatif");
        $this->get("/{$store->slug}/commander/recapitulatif")->assertSee('Lomé centre')->assertSee('17 000 FCFA');
        $this->post("/{$store->slug}/commander/valider", ['channel' => 'web', 'delivery_fee' => 0]);

        $order = Order::firstOrFail();
        $this->assertSame('16000.00', (string) $order->subtotal);
        $this->assertSame('1000.00', (string) $order->delivery_fee);
        $this->assertSame('17000.00', (string) $order->total);
        $this->assertSame('Lomé centre', $order->delivery_zone_name);
        $this->assertStringContainsString('Livraison (Lomé centre) : 1 000 FCFA', app(OrderService::class)->whatsappMessage($order));
    }

    public function test_zone_is_required_when_the_store_has_zones(): void
    {
        $store = Store::factory()->create();
        DeliveryZone::factory()->for($store)->create();
        $product = Product::factory()->for($store)->create();
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id]);

        $this->post("/{$store->slug}/commander", $this->customer)->assertSessionHasErrors('delivery_zone_id');

        $this->expectException(ValidationException::class);
        app(OrderService::class)->place($store, [$product->id => 1], $this->customer);
    }

    public function test_zone_of_another_store_or_inactive_zone_is_refused(): void
    {
        $store = Store::factory()->create();
        DeliveryZone::factory()->for($store)->create();
        $inactive = DeliveryZone::factory()->for($store)->create(['is_active' => false, 'fee' => 0]);
        $foreign = DeliveryZone::factory()->create(['fee' => 0]);
        $product = Product::factory()->for($store)->create();
        $this->post("/{$store->slug}/panier", ['product_id' => $product->id]);

        $this->post("/{$store->slug}/commander", $this->customer + ['delivery_zone_id' => $foreign->id])->assertSessionHasErrors('delivery_zone_id');
        $this->post("/{$store->slug}/commander", $this->customer + ['delivery_zone_id' => $inactive->id])->assertSessionHasErrors('delivery_zone_id');

        try {
            app(OrderService::class)->place($store, [$product->id => 1], $this->customer + ['delivery_zone_id' => $foreign->id]);
            $this->fail('Zone étrangère acceptée');
        } catch (ValidationException) {
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_free_delivery_threshold(): void
    {
        $store = Store::factory()->create(['free_delivery_threshold' => 20000]);
        $zone = DeliveryZone::factory()->for($store)->create(['fee' => 1500]);
        $product = Product::factory()->for($store)->create(['price' => 10000]);
        $service = app(OrderService::class);

        $small = $service->place($store, [$product->id => 1], $this->customer + ['delivery_zone_id' => $zone->id]);
        $big = $service->place($store, [$product->id => 2], $this->customer + ['delivery_zone_id' => $zone->id]);

        $this->assertSame('11500.00', (string) $small->total);
        $this->assertSame('0.00', (string) $big->delivery_fee);
        $this->assertSame('20000.00', (string) $big->total);
    }

    public function test_store_without_zones_keeps_working_without_fees(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->create(['price' => 5000]);

        $order = app(OrderService::class)->place($store, [$product->id => 1], $this->customer);

        $this->assertSame('0.00', (string) $order->delivery_fee);
        $this->assertSame('5000.00', (string) $order->total);
        $this->assertNull($order->delivery_zone_name);
    }

    public function test_order_keeps_fee_snapshot_after_zone_change_or_deletion(): void
    {
        $store = Store::factory()->create();
        $zone = DeliveryZone::factory()->for($store)->create(['name' => 'Agoè', 'fee' => 1500]);
        $product = Product::factory()->for($store)->create(['price' => 5000]);
        $order = app(OrderService::class)->place($store, [$product->id => 1], $this->customer + ['delivery_zone_id' => $zone->id]);

        $zone->update(['fee' => 3000]);
        $zone->delete();

        $order->refresh();
        $this->assertSame('Agoè', $order->delivery_zone_name);
        $this->assertSame('1500.00', (string) $order->delivery_fee);
        $this->assertSame('6500.00', (string) $order->total);
    }

    public function test_vendor_manages_zones_of_his_store_only(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        [, $other] = $this->vendorWithStore();
        $foreignZone = DeliveryZone::factory()->for($other)->create(['fee' => 500]);

        $this->actingAs($vendor)->get(route('vendor.delivery.index', $store))->assertOk();
        $this->actingAs($vendor)->post(route('vendor.delivery.store', $store), ['name' => 'Bè', 'fee' => 1000, 'is_active' => 1])->assertSessionHas('success');
        $this->actingAs($vendor)->put(route('vendor.delivery.options', $store), ['free_delivery_threshold' => 30000])->assertSessionHas('success');

        $this->assertDatabaseHas('delivery_zones', ['store_id' => $store->id, 'name' => 'Bè']);
        $this->assertSame('30000.00', (string) $store->fresh()->free_delivery_threshold);

        $this->actingAs($vendor)->put("/vendeur/{$store->slug}/livraison/{$foreignZone->id}", ['name' => 'Piraté', 'fee' => 0])->assertNotFound();
        $this->actingAs($vendor)->delete("/vendeur/{$store->slug}/livraison/{$foreignZone->id}")->assertNotFound();
        $this->assertSame('500.00', (string) $foreignZone->fresh()->fee);
    }
}
