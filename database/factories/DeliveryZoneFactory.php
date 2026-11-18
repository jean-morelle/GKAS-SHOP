<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Zone '.fake()->unique()->city(),
            'description' => null,
            'fee' => 1000,
            'is_active' => true,
            'position' => 0,
        ];
    }
}
