<?php

namespace Database\Factories;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RangeScenario>
 */
class RangeScenarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'range_study_id' => RangeStudy::factory(),
            'group_label' => 'Sin oposición',
            'group_order' => 0,
            'row_label' => 'EP',
            'row_order' => 0,
            'button_label' => '100BB',
            'button_order' => 0,
            'is_default' => false,
            'legend' => [
                ['key' => 'raise', 'label' => 'Raise', 'color' => '#22c55e'],
                ['key' => 'fold', 'label' => 'Fold', 'color' => '#ef4444'],
            ],
            'combos' => ['AA' => 'raise', 'KK' => 'raise', '72o' => 'fold'],
            'stats' => [
                'badges' => [['label' => 'VPIP', 'value' => '16.9%', 'highlight' => false]],
                'bars' => [['label' => 'F', 'pct' => 65, 'color' => '#ef4444']],
            ],
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
