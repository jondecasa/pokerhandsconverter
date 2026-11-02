<?php

namespace App\Poker;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use Illuminate\Support\Str;

/**
 * Every chart of the Ranges section as ready-to-load ranges for the Flopzer analyser.
 *
 * A chart marks each hand with an action (OR, call, raise, 4bet…). Each action becomes an option on its own, and when
 * several actions are marked a last option takes them all together (everything but fold). A range is plain text the
 * analyser already reads.
 */
class FlopzerPresets
{
    /**
     * @return list<array{name: string, groups: list<array{label: string, charts: list<array{position: string, stack: string, options: list<array{label: string, text: string, hands: int}>}>}>}>
     */
    public static function all(): array
    {
        return RangeStudy::ordered()->with('scenarios')->get()
            ->map(fn (RangeStudy $study) => [
                'name' => $study->name,
                'groups' => $study->scenarios
                    ->groupBy('group_label')
                    ->map(fn ($scenarios, $label) => [
                        'label' => (string) $label,
                        'charts' => $scenarios
                            ->map(fn (RangeScenario $scenario) => [
                                'position' => $scenario->row_label,
                                'stack' => $scenario->button_label,
                                'options' => self::options($scenario),
                            ])
                            ->filter(fn (array $chart) => $chart['options'] !== [])
                            ->values()
                            ->all(),
                    ])
                    ->filter(fn (array $group) => $group['charts'] !== [])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $study) => $study['groups'] !== [])
            ->values()
            ->all();
    }

    /** @return list<array{label: string, text: string, hands: int}> */
    private static function options(RangeScenario $scenario): array
    {
        $labels = collect($scenario->legend)->pluck('label', 'key');
        $keys = $labels->keys()
            ->reject(fn ($key) => $key === 'fold' || strcasecmp((string) $labels[$key], 'fold') === 0)
            ->values();
        if ($keys->isEmpty()) {
            return [];
        }

        $options = [];
        $add = function (string $label, array $use) use ($scenario, &$options): void {
            $hands = collect($scenario->combos)->filter(fn ($action) => in_array($action, $use, true))->keys();
            if ($hands->isNotEmpty()) {
                $options[] = ['label' => $label, 'text' => $hands->implode(', '), 'hands' => $hands->count()];
            }
        };

        // an action named after the first one ("OR" and "OR ampliación") is an extension: it is not offered alone
        $first = (string) $labels[$keys[0]];
        foreach ($keys as $index => $key) {
            if ($index === 0 || ! Str::startsWith((string) $labels[$key], $first.' ')) {
                $add((string) $labels[$key], [$key]);
            }
        }

        if ($keys->count() > 1) {
            $rest = $keys->slice(1)->map(fn ($key) => Str::of($labels[$key])->after($first.' ')->toString());
            $add($keys->count() > 2 ? 'Everything but fold' : $first.' + '.$rest->first(), $keys->all());
        }

        return $options;
    }
}
