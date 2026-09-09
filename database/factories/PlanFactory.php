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
        $name = fake()->unique()->randomElement(['Micro', 'Low', 'Mid', 'High', 'Nosebleed']).' Stakes';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(6),
            'price' => fake()->randomElement([9, 19, 29, 90, 190]),
            'currency' => 'USD',
            'interval' => fake()->randomElement(['month', 'year']),
            'stripe_price_id' => null,
            'stakes_cap' => fake()->randomElement(config('pokercoinverter.stakes')),
            'stakes_label' => null,
            'features' => ['Unlimited conversions', 'Cash & tournaments', 'Conversion history'],
            'trial_days' => null,
            'is_visible' => true,
            'is_active' => true,
            'is_highlighted' => false,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
