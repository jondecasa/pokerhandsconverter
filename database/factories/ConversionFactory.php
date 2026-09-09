<?php

namespace Database\Factories;

use App\Models\Conversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversion>
 */
class ConversionFactory extends Factory
{
    public function definition(): array
    {
        $hands = fake()->numberBetween(1, 500);

        return [
            'user_id' => User::factory(),
            'original_filename' => 'HH'.fake()->numerify('########').'.txt',
            'output_path' => 'conversions/'.fake()->uuid().'.txt',
            'hand_count' => $hands,
            'warning_count' => 0,
            'input_bytes' => $hands * 800,
            'format_breakdown' => ['Cash' => $hands],
            'warnings' => [],
        ];
    }
}
