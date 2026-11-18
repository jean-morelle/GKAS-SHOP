<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_pages_are_available(): void
    {
        foreach (['/', '/fonctionnalites', '/comment-ca-marche', '/demonstration', '/tarifs', '/faq', '/contact', '/demande-essai', '/conditions', '/confidentialite'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_demo_request_is_recorded(): void
    {
        $this->post('/demande-essai', ['name' => 'Kossi', 'phone' => '+228 90 12 34 56', 'social_network' => 'tiktok', 'activity' => 'Chaussures'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('demo_requests', ['name' => 'Kossi', 'status' => 'new', 'type' => 'demo']);
    }

    public function test_store_shows_only_active_products_with_fcfa_prices(): void
    {
        $store = Store::factory()->create();
        Product::factory()->for($store)->create(['name' => 'Produit visible', 'price' => 8000]);
        Product::factory()->for($store)->inactive()->create(['name' => 'Produit masqué']);
        Product::factory()->create(['name' => 'Produit autre boutique']);

        $this->get("/{$store->slug}")
            ->assertOk()
            ->assertSee('Produit visible')
            ->assertSee('8 000 FCFA')
            ->assertDontSee('Produit masqué')
            ->assertDontSee('Produit autre boutique');
    }

    public function test_inactive_product_page_returns_404(): void
    {
        $store = Store::factory()->create();
        $product = Product::factory()->for($store)->inactive()->create();

        $this->get("/{$store->slug}/produit/{$product->slug}")->assertNotFound();
    }

    public function test_product_of_another_store_is_not_reachable(): void
    {
        $store = Store::factory()->create();
        $other = Product::factory()->create();

        $this->get("/{$store->slug}/produit/{$other->slug}")->assertNotFound();
    }

    public function test_search_filters_products(): void
    {
        $store = Store::factory()->create();
        Product::factory()->for($store)->create(['name' => 'Sac en cuir']);
        Product::factory()->for($store)->create(['name' => 'Montre dorée']);

        $this->get("/{$store->slug}?q=cuir")->assertSee('Sac en cuir')->assertDontSee('Montre dorée');
    }

    public function test_expired_store_shows_unavailable_page(): void
    {
        $store = Store::factory()->expired()->create(['name' => 'Boutique Expirée']);

        $this->get("/{$store->slug}")->assertForbidden()->assertSee('momentanément indisponible');
    }

    public function test_unpublished_store_is_hidden_from_public_but_previewable_by_owner(): void
    {
        [$vendor, $store] = $this->vendorWithStore(['is_published' => false]);

        $this->get("/{$store->slug}")->assertNotFound();
        $this->actingAs($vendor)->get("/{$store->slug}")->assertOk()->assertSee('Prévisualisation');
    }

    public function test_gkas_staff_can_preview_an_unpublished_store_but_other_vendors_cannot(): void
    {
        [, $store] = $this->vendorWithStore(['is_published' => false]);
        $otherVendor = \App\Models\User::factory()->create();

        $this->actingAs(\App\Models\User::factory()->admin()->create())->get("/{$store->slug}")->assertOk()->assertSee('Prévisualisation');
        $this->actingAs(\App\Models\User::factory()->sales()->create())->get("/{$store->slug}")->assertOk();
        $this->actingAs($otherVendor)->get("/{$store->slug}")->assertNotFound();
    }

    public function test_archived_store_returns_404(): void
    {
        $store = Store::factory()->create(['status' => 'archived']);

        $this->get("/{$store->slug}")->assertNotFound();
    }
}
