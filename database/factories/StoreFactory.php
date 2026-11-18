<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Enums\StoreTheme;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(12),
            'primary_color' => '#4f46e5',
            'theme' => StoreTheme::Moderne,
            'whatsapp' => '+228 90 00 00 00',
            'status' => StoreStatus::Active,
            'is_published' => true,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays(30),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Store $store) {
            if ($store->owner_id) {
                $store->users()->syncWithoutDetaching([$store->owner_id => ['role' => 'owner']]);
            }
        });
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'trial_starts_at' => now()->subDays(40),
            'trial_ends_at' => now()->subDays(10),
            'subscription_ends_at' => null,
        ]);
    }

    public function subscribed(int $days = 30): static
    {
        return $this->state(fn () => [
            'subscription_activated_at' => now()->subDay(),
            'subscription_ends_at' => now()->addDays($days),
        ]);
    }

    public function suspended(string $reason = 'Non-respect des conditions'): static
    {
        return $this->state(fn () => [
            'status' => StoreStatus::Suspended,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
