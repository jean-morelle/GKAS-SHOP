<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_vendor_can_create_a_product_with_images(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $category = Category::factory()->for($store)->create();

        $this->actingAs($vendor)->post(route('vendor.products.store', $store), [
            'name' => 'Robe wax',
            'description' => 'Belle robe',
            'price' => '15 000',
            'sale_price' => 12000,
            'category_id' => $category->id,
            'stock_quantity' => 5,
            'is_active' => '1',
            'images' => [$this->fakePng('a.png'), $this->fakePng('b.png')],
        ])->assertRedirect(route('vendor.products.index', $store));

        $product = Product::where('name', 'Robe wax')->firstOrFail();
        $this->assertSame($store->id, $product->store_id);
        $this->assertSame('15000.00', (string) $product->price);
        $this->assertSame('12000.00', $product->effectivePrice());
        $this->assertCount(2, $product->images);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith("stores/{$store->id}/products/", $image->path);
            $this->assertStringNotContainsString('a.png', $image->path); // nom de fichier aléatoire
        }
    }

    public function test_vendor_can_update_a_product(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $product = Product::factory()->for($store)->create(['price' => 5000]);

        $this->actingAs($vendor)->put(route('vendor.products.update', [$store, $product]), [
            'name' => 'Nouveau nom', 'price' => 6500, 'is_active' => '1',
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('Nouveau nom', $product->name);
        $this->assertSame('6500.00', (string) $product->price);
    }

    public function test_vendor_can_deactivate_and_delete_a_product(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $product = Product::factory()->for($store)->create();

        $this->actingAs($vendor)->post(route('vendor.products.toggle', [$store, $product]))->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($vendor)->delete(route('vendor.products.destroy', [$store, $product]))->assertRedirect();
        $this->assertSoftDeleted($product);
    }

    public function test_non_image_file_is_rejected(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $fake = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "hack"; ?>');

        $this->actingAs($vendor)->post(route('vendor.products.store', $store), [
            'name' => 'Test', 'price' => 1000, 'images' => [$fake],
        ])->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_svg_and_oversized_images_are_rejected(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $big = UploadedFile::fake()->create('big.png', 5000, 'image/png');

        $this->actingAs($vendor)->post(route('vendor.products.store', $store), [
            'name' => 'Test', 'price' => 1000, 'images' => [$svg, $big],
        ])->assertSessionHasErrors(['images.0', 'images.1']);
    }

    public function test_product_form_validation(): void
    {
        [$vendor, $store] = $this->vendorWithStore();

        $this->actingAs($vendor)->post(route('vendor.products.store', $store), [
            'name' => '', 'price' => -5, 'sale_price' => 100,
        ])->assertSessionHasErrors(['name', 'price']);

        $this->actingAs($vendor)->post(route('vendor.products.store', $store), [
            'name' => 'Promo invalide', 'price' => 5000, 'sale_price' => 6000,
        ])->assertSessionHasErrors('sale_price');
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        [$vendor, $store] = $this->vendorWithStore();
        $category = Category::factory()->for($store)->create();
        Product::factory()->for($store)->create(['category_id' => $category->id]);

        $this->actingAs($vendor)->delete(route('vendor.categories.destroy', [$store, $category]))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_expired_store_cannot_modify_catalog_but_can_read_it(): void
    {
        [$vendor, $store] = $this->vendorWithStore(['trial_starts_at' => now()->subDays(40), 'trial_ends_at' => now()->subDays(10)]);

        $this->actingAs($vendor)->get(route('vendor.products.index', $store))->assertOk();
        $this->actingAs($vendor)->post(route('vendor.products.store', $store), ['name' => 'X', 'price' => 1000])
            ->assertRedirect(route('vendor.subscription.show', $store));
        $this->assertDatabaseCount('products', 0);
        $this->actingAs($vendor)->get(route('vendor.export', [$store, 'produits']))->assertOk();
    }
}
