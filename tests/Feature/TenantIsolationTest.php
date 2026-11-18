<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_cannot_open_another_vendors_store(): void
    {
        [$vendorA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();

        $this->actingAs($vendorA)->get(route('vendor.dashboard', $storeB))->assertNotFound();
        $this->actingAs($vendorA)->get(route('vendor.orders.index', $storeB))->assertNotFound();
        $this->actingAs($vendorA)->get(route('vendor.products.index', $storeB))->assertNotFound();
        $this->actingAs($vendorA)->get(route('vendor.export', [$storeB, 'commandes']))->assertNotFound();
    }

    public function test_vendor_cannot_reach_other_store_product_by_changing_id_in_url(): void
    {
        [$vendorA, $storeA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $productB = Product::factory()->for($storeB)->create(['name' => 'Produit B']);

        // L'identifiant du produit B placé sous la boutique A : liaison scopée → 404.
        $this->actingAs($vendorA)->get("/vendeur/{$storeA->slug}/produits/{$productB->id}/modifier")->assertNotFound();
        $this->actingAs($vendorA)->put("/vendeur/{$storeA->slug}/produits/{$productB->id}", ['name' => 'Piraté', 'price' => 1])->assertNotFound();
        $this->actingAs($vendorA)->delete("/vendeur/{$storeA->slug}/produits/{$productB->id}")->assertNotFound();

        $this->assertSame('Produit B', $productB->fresh()->name);
        $this->assertNull($productB->fresh()->deleted_at);
    }

    public function test_vendor_cannot_see_or_update_another_stores_order(): void
    {
        [$vendorA, $storeA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $product = Product::factory()->for($storeB)->create();
        $order = app(OrderService::class)->place($storeB, [$product->id => 1], [
            'name' => 'Client', 'phone' => '90000000', 'area' => 'Bè', 'address' => 'Rue 1',
        ]);

        $this->actingAs($vendorA)->get("/vendeur/{$storeA->slug}/commandes/{$order->reference}")->assertNotFound();
        $this->actingAs($vendorA)->post("/vendeur/{$storeA->slug}/commandes/{$order->reference}/statut", ['status' => 'confirmed'])->assertNotFound();
        $this->assertSame('new', $order->fresh()->status->value);
    }

    public function test_vendor_cannot_attach_a_category_from_another_store(): void
    {
        [$vendorA, $storeA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $categoryB = Category::factory()->for($storeB)->create();

        $this->actingAs($vendorA)->post(route('vendor.products.store', $storeA), [
            'name' => 'Sac', 'price' => 5000, 'category_id' => $categoryB->id,
        ])->assertSessionHasErrors('category_id');
    }

    public function test_dashboard_statistics_only_count_own_orders(): void
    {
        [$vendorA, $storeA] = $this->vendorWithStore();
        [, $storeB] = $this->vendorWithStore();
        $product = Product::factory()->for($storeB)->create();
        app(OrderService::class)->place($storeB, [$product->id => 1], ['name' => 'Secret Client', 'phone' => '90000000', 'area' => 'X', 'address' => 'Y']);

        $this->actingAs($vendorA)->get(route('vendor.dashboard', $storeA))
            ->assertOk()
            ->assertDontSee('Secret Client');
    }
}
