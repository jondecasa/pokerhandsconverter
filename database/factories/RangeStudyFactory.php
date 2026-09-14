<?php

namespace Database\Factories;

use App\Models\RangeStudy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RangeStudy>
 */
class RangeStudyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['6MAX 100BB', '6MAX 50BB', 'HU 100BB', 'MTT Late Reg']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
