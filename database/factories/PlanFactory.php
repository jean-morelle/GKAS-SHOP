<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Formule '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Formule de test',
            'price' => 10000,
            'duration_days' => 30,
            'features' => ['Produits illimités'],
            'is_active' => true,
            'position' => 0,
        ];
    }
}
