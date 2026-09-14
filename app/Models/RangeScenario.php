<?php

namespace App\Models;

use App\Poker\PreflopGrid;
use Database\Factories\RangeScenarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangeScenario extends Model
{
    /** @use HasFactory<RangeScenarioFactory> */
    use HasFactory;

    protected $fillable = [
        'range_study_id',
        'group_label', 'group_order',
        'row_label', 'row_order',
        'button_label', 'button_order',
        'is_default',
        'legend', 'combos', 'stats',
    ];

    protected function casts(): array
    {
        return [
            'group_order' => 'integer',
            'row_order' => 'integer',
            'button_order' => 'integer',
            'is_default' => 'boolean',
            'legend' => 'array',
            'combos' => 'array',
            'stats' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RangeStudy, $this>
     */
    public function study(): BelongsTo
    {
        return $this->belongsTo(RangeStudy::class, 'range_study_id');
    }

    /** The 13x13 grid, each cell paired with its action color from the legend. */
    public function grid(): array
    {
        $colors = collect($this->legend)->pluck('color', 'key');

        return array_map(
            fn (array $row) => array_map(fn (string $hand) => [
                'hand' => $hand,
                'action' => $this->combos[$hand] ?? null,
                'color' => $colors[$this->combos[$hand] ?? null] ?? null,
            ], $row),
            PreflopGrid::rows()
        );
    }

    /** Same as grid(), flattened to the 169 cells in row-major order. */
    public function flatGrid(): array
    {
        return array_merge(...$this->grid());
    }
}
