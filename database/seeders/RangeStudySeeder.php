<?php

namespace Database\Seeders;

use App\Models\RangeStudy;
use Illuminate\Database\Seeder;

/**
 * Preflop range studies, transcribed from the user's reference charts.
 * Each study's scenarios live in a JSON fixture under seeders/data/ since
 * a single study can hold dozens of hand-painted 13x13 grids.
 */
class RangeStudySeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(__DIR__.'/data/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);

            // The folder also holds other seeders' fixtures (posts.json).
            if (is_array($data) && isset($data['scenarios'])) {
                $this->seedStudy($data);
            }
        }
    }

    private function seedStudy(array $data): void
    {
        $study = RangeStudy::updateOrCreate(
            ['slug' => $data['slug']],
            ['name' => $data['name'], 'sort_order' => $data['sort_order'], 'row_colors' => $data['row_colors'] ?? null],
        );

        foreach ($data['scenarios'] as $scenario) {
            $study->scenarios()->updateOrCreate(
                [
                    'group_label' => $scenario['group_label'],
                    'row_label' => $scenario['row_label'],
                    'button_label' => $scenario['button_label'],
                ],
                [
                    'group_order' => $scenario['group_order'],
                    'row_order' => $scenario['row_order'],
                    'button_order' => $scenario['button_order'],
                    'is_default' => $scenario['is_default'],
                    'legend' => $scenario['legend'],
                    'combos' => $scenario['combos'],
                    'stats' => $scenario['stats'],
                ] + (isset($scenario['quiz_enabled']) ? ['quiz_enabled' => $scenario['quiz_enabled']] : [])
            );
        }
    }
}
