<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(15),
            'price' => fake()->randomElement([2500, 5000, 8000, 12500, 15000]),
            'sale_price' => null,
            'stock_quantity' => null,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function onSale(int $salePrice): static
    {
        return $this->state(fn () => ['sale_price' => $salePrice]);
    }

    public function stock(int $quantity): static
    {
        return $this->state(fn () => ['stock_quantity' => $quantity]);
    }
}
