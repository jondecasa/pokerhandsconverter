<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RangeScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'group_label' => ['required', 'string', 'max:60'],
            'group_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'row_label' => ['required', 'string', 'max:60'],
            'row_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'button_label' => ['required', 'string', 'max:60'],
            'button_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_default' => ['boolean'],

            'legend' => ['required', 'array', 'min:1'],
            'legend.*.key' => ['required', 'string', 'max:40'],
            'legend.*.label' => ['required', 'string', 'max:60'],
            'legend.*.color' => ['required', 'string', 'max:20'],

            'combos' => ['nullable', 'array'],
            'combos.*' => ['nullable', 'string', 'max:40'],

            'stats' => ['nullable', 'array'],
            'stats.badges' => ['nullable', 'array'],
            'stats.badges.*.label' => ['nullable', 'string', 'max:60'],
            'stats.badges.*.value' => ['nullable', 'string', 'max:60'],
            'stats.badges.*.highlight' => ['boolean'],
            'stats.bars' => ['nullable', 'array'],
            'stats.bars.*.label' => ['nullable', 'string', 'max:20'],
            'stats.bars.*.pct' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stats.bars.*.color' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Drop blank placeholder rows (an admin adding then not filling a legend/
     * badge/bar row, or clearing a grid cell) before validation runs, so
     * "required" rules only apply to rows that actually carry data.
     */
    protected function prepareForValidation(): void
    {
        $legend = collect($this->input('legend', []))
            ->filter(fn ($row) => filled($row['key'] ?? null) && filled($row['label'] ?? null))
            ->map(fn ($row) => ['key' => $row['key'], 'label' => $row['label'], 'color' => $row['color'] ?? '#94a3b8'])
            ->values()->all();

        $combos = collect($this->input('combos', []))
            ->filter(fn ($action) => filled($action))
            ->all();

        $badges = collect($this->input('stats.badges', []))
            ->filter(fn ($row) => filled($row['label'] ?? null) || filled($row['value'] ?? null))
            ->map(fn ($row) => [
                'label' => $row['label'] ?? '',
                'value' => $row['value'] ?? '',
                'highlight' => filter_var($row['highlight'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])->values()->all();

        $bars = collect($this->input('stats.bars', []))
            ->filter(fn ($row) => filled($row['label'] ?? null))
            ->map(fn ($row) => [
                'label' => $row['label'] ?? '',
                'pct' => (int) ($row['pct'] ?? 0),
                'color' => $row['color'] ?? '#94a3b8',
            ])->values()->all();

        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'legend' => $legend,
            'combos' => $combos,
            'stats' => ($badges || $bars) ? ['badges' => $badges, 'bars' => $bars] : null,
        ]);
    }

    /** Data ready for RangeScenario::create()/update(). */
    public function scenarioData(): array
    {
        $data = $this->safe()->only([
            'group_label', 'group_order', 'row_label', 'row_order',
            'button_label', 'button_order', 'is_default', 'legend', 'combos', 'stats',
        ]);

        $data['group_order'] ??= 0;
        $data['row_order'] ??= 0;
        $data['button_order'] ??= 0;

        return $data;
    }
}
