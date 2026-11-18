<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    private function storeWithDomain(array $attributes = []): Store
    {
        $store = Store::factory()->create($attributes);
        $store->forceFill(['custom_domain' => 'bellafashion.com'])->save();

        return $store;
    }

    public function test_store_is_served_at_the_root_of_its_custom_domain(): void
    {
        $store = $this->storeWithDomain(['name' => 'Bella Fashion']);
        $product = Product::factory()->for($store)->create(['name' => 'Robe wax']);

        $this->get('http://bellafashion.com/')
            ->assertOk()
            ->assertSee('Bella Fashion')
            ->assertSee('Robe wax')
            // Les liens restent sur le domaine du vendeur.
            ->assertSee('http://bellafashion.com/produit/'.$product->slug, false);

        $this->get('http://bellafashion.com/produit/'.$product->slug)->assertOk()->assertSee('Robe wax');
    }

    public function test_full_order_flow_on_custom_domain(): void
    {
        $store = $this->storeWithDomain();
        $product = Product::factory()->for($store)->create(['price' => 8000]);

        $this->post('http://bellafashion.com/panier', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('http://bellafashion.com/commander', ['name' => 'Ama', 'phone' => '+228 90 11 22 33', 'area' => 'Bè', 'address' => 'Rue 1'])
            ->assertRedirect('http://bellafashion.com/commander/recapitulatif');
        $this->post('http://bellafashion.com/commander/valider', ['channel' => 'web']);

        $order = Order::firstOrFail();
        $this->assertSame($store->id, $order->store_id);
        $this->get('http://bellafashion.com/commande/'.$order->reference)->assertOk()->assertSee($order->reference);
    }

    public function test_platform_host_is_unaffected_and_slug_address_still_works(): void
    {
        $store = $this->storeWithDomain();

        $this->get('/')->assertOk()->assertSee('Demander une boutique d');
        $this->get("/{$store->slug}")->assertOk();
    }

    public function test_unknown_domain_returns_404(): void
    {
        $this->storeWithDomain();

        $this->get('http://inconnu-boutique.com/')->assertNotFound();
    }

    public function test_product_of_another_store_is_not_reachable_through_a_custom_domain(): void
    {
        $this->storeWithDomain();
        $other = Product::factory()->create();

        $this->get('http://bellafashion.com/produit/'.$other->slug)->assertNotFound();
    }

    public function test_expired_store_custom_domain_shows_unavailable_page(): void
    {
        $this->storeWithDomain(['trial_starts_at' => now()->subDays(40), 'trial_ends_at' => now()->subDays(10)]);

        $this->get('http://bellafashion.com/')->assertForbidden()->assertSee('momentanément indisponible');
    }

    public function test_only_admin_can_assign_a_domain_and_it_is_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->sales()->create();
        $store = Store::factory()->create();
        $other = $this->storeWithDomain();

        $this->actingAs($sales)->put(route('admin.stores.domain', $store), ['custom_domain' => 'kente.com'])->assertForbidden();

        $this->actingAs($admin)->put(route('admin.stores.domain', $store), ['custom_domain' => 'https://www.Kente-Store.com/'])->assertSessionHas('success');
        $this->assertSame('www.kente-store.com', $store->fresh()->custom_domain);
        $this->assertSame('https://www.kente-store.com', $store->fresh()->publicUrl());
        $this->assertDatabaseHas('activity_logs', ['action' => 'store.domain_changed']);

        $this->actingAs($admin)->put(route('admin.stores.domain', $store), ['custom_domain' => 'bellafashion.com'])->assertSessionHasErrors('custom_domain');
        $this->actingAs($admin)->put(route('admin.stores.domain', $store), ['custom_domain' => 'pas un domaine'])->assertSessionHasErrors('custom_domain');
        $this->actingAs($admin)->put(route('admin.stores.domain', $store), ['custom_domain' => 'localhost'])->assertSessionHasErrors('custom_domain');

        $this->actingAs($admin)->put(route('admin.stores.domain', $store), ['custom_domain' => ''])->assertSessionHas('success');
        $this->assertNull($store->fresh()->custom_domain);
        $this->assertSame('bellafashion.com', $other->fresh()->custom_domain);
    }
}
