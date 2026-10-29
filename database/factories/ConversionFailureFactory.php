<?php

namespace Database\Factories;

use App\Models\ConversionFailure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversionFailure>
 */
class ConversionFailureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => 'HH'.fake()->numerify('########').'.txt',
            'file_size' => fake()->numberBetween(0, 5_000_000),
            'reason' => 'no_hands',
            'message' => 'No hands could be parsed from that file.',
            'details' => 'First line: Hand #123',
            'ip' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0 (test)',
        ];
    }
}
