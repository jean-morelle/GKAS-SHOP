<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private function order($store, $product, int $qty = 1)
    {
        return app(OrderService::class)->place($store, [$product->id => $qty], [
            'name' => 'Client', 'phone' => '90000000', 'area' => 'Bè', 'address' => 'Rue 1',
        ]);
    }

    public function test_vendor_can_apply_allowed_transitions(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $order = $this->order($store, Product::factory()->for($store)->create());

        $this->actingAs($vendor)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'confirmed', 'note' => 'OK au téléphone'])
            ->assertSessionHas('success');
        $this->assertSame('confirmed', $order->fresh()->status->value);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'from_status' => 'new', 'to_status' => 'confirmed', 'user_id' => $vendor->id]);
    }

    public function test_forbidden_transition_is_refused(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $order = $this->order($store, Product::factory()->for($store)->create());

        // Nouvelle → Livrée directement : interdit.
        $this->actingAs($vendor)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'delivered'])
            ->assertSessionHasErrors('status');
        $this->assertSame('new', $order->fresh()->status->value);
    }

    public function test_cancelled_order_cannot_be_reopened_and_restores_stock(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $product = Product::factory()->for($store)->stock(5)->create();
        $order = $this->order($store, $product, 2);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->actingAs($vendor)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'cancelled']);
        $this->assertSame(5, $product->fresh()->stock_quantity);

        $this->actingAs($vendor)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'confirmed'])
            ->assertSessionHasErrors('status');
        $this->assertSame('cancelled', $order->fresh()->status->value);
    }

    public function test_admin_cannot_change_vendor_order_status_from_vendor_space(): void
    {
        [, $store] = $this->vendorWithStore();
        $admin = User::factory()->admin()->create();
        $order = $this->order($store, Product::factory()->for($store)->create());

        $this->actingAs($admin)->post(route('vendor.orders.status', [$store, $order]), ['status' => 'confirmed'])->assertNotFound();
        $this->assertSame('new', $order->fresh()->status->value);
    }

    public function test_sales_summary_counts_only_delivered_or_paid_orders(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $product = Product::factory()->for($store)->create(['price' => 10000]);
        $service = app(OrderService::class);

        $delivered = $this->order($store, $product);
        foreach (['confirmed', 'ready', 'delivered'] as $s) {
            $service->changeStatus($delivered, \App\Enums\OrderStatus::from($s), null, $vendor);
        }
        $this->order($store, $product); // nouvelle, non payée : non comptée

        $this->actingAs($vendor)->get(route('vendor.dashboard', $store))->assertOk()->assertSee('10 000 FCFA');
        $this->assertSame(10000.0, (float) $store->orders()->countedAsSale()->sum('total'));
    }
}
