<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(private readonly ImageUploader $uploader) {}

    /** @param  list<UploadedFile>  $images */
    public function create(Store $store, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($store, $data, $images) {
            $product = new Product($data);
            $product->store_id = $store->id;
            $product->slug = $this->uniqueSlug($store, $data['name']);
            $product->save();

            $this->attachImages($product, $images);

            return $product;
        });
    }

    /** @param  list<UploadedFile>  $images */
    public function update(Product $product, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images) {
            if ($data['name'] !== $product->name) {
                $product->slug = $this->uniqueSlug($product->store, $data['name'], $product->id);
            }
            $product->fill($data)->save();

            $this->attachImages($product, $images);

            return $product;
        });
    }

    /** @param  list<UploadedFile>  $images */
    private function attachImages(Product $product, array $images): void
    {
        if (! $images) {
            return;
        }

        $max = config('gkas.uploads.max_product_images');
        $existing = $product->images()->count();

        if ($existing + count($images) > $max) {
            throw ValidationException::withMessages(['images' => "Un produit peut avoir au maximum {$max} images."]);
        }

        $position = (int) $product->images()->max('position');

        foreach ($images as $file) {
            $product->images()->create([
                'path' => $this->uploader->store($file, "stores/{$product->store_id}/products"),
                'position' => ++$position,
            ]);
        }
    }

    private function uniqueSlug(Store $store, string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'produit', 170, '');
        $slug = $base;
        $i = 2;

        while ($store->products()->withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
