<?php

namespace App\Poker;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use Illuminate\Support\Collection;

/**
 * Builds a quiz from a range study: random (scenario, hand) questions whose
 * right answer is what the chart paints on that hand. A hand the chart leaves
 * unpainted is a fold.
 */
class RangeQuiz
{
    public const QUESTIONS = 20;

    /** Share of the questions that use a hand the chart actually paints, so the quiz isn't mostly easy folds. */
    private const PAINTED_SHARE = 0.7;

    private const FOLD = ['key' => 'fold', 'label' => 'Fold', 'color' => '#ef4444'];

    private const SUITS = [
        ['symbol' => '♠', 'red' => false],
        ['symbol' => '♥', 'red' => true],
        ['symbol' => '♦', 'red' => true],
        ['symbol' => '♣', 'red' => false],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function questions(RangeStudy $study, int $count = self::QUESTIONS): array
    {
        $scenarios = self::quizScenarios($study);

        if ($scenarios->isEmpty()) {
            return [];
        }

        $paintedSlots = (int) ceil($count * self::PAINTED_SHARE);
        $slots = array_merge(array_fill(0, $paintedSlots, true), array_fill(0, $count - $paintedSlots, false));

        $used = [];
        $questions = [];

        foreach ($slots as $wantsPainted) {
            $question = self::pick($study, $scenarios->shuffle(), $wantsPainted, $used)
                ?? self::pick($study, $scenarios->shuffle(), ! $wantsPainted, $used);

            if ($question === null) {
                break;
            }

            $used[$question['scenario_id'].':'.$question['hand']] = true;
            $questions[] = $question;
        }

        shuffle($questions);

        return array_map(fn (array $question, int $position) => ['id' => $position + 1] + $question, $questions, array_keys($questions));
    }

    /** @return Collection<int, RangeScenario> */
    public static function quizScenarios(RangeStudy $study): Collection
    {
        return $study->scenarios()
            ->where('quiz_enabled', true)
            ->get()
            ->filter(fn (RangeScenario $scenario) => ! empty($scenario->combos))
            ->values();
    }

    /**
     * The answer buttons for a scenario: its own actions, plus Fold when the
     * chart doesn't list one (an open-raise chart only paints the raises).
     *
     * @return list<array{key: string, label: string, color: string}>
     */
    public static function options(RangeScenario $scenario): array
    {
        $options = array_map(fn (array $row) => [
            'key' => (string) $row['key'],
            'label' => (string) $row['label'],
            'color' => (string) ($row['color'] ?? '#94a3b8'),
        ], $scenario->legend ?? []);

        if (! in_array('fold', array_column($options, 'key'), true)) {
            array_unshift($options, self::FOLD);
        }

        return $options;
    }

    /** What the chart says to do with a hand. */
    public static function correctAction(RangeScenario $scenario, string $hand): string
    {
        return $scenario->combos[$hand] ?? 'fold';
    }

    /**
     * @param  Collection<int, RangeScenario>  $scenarios  in the order to try them
     * @param  array<string, true>  $used
     * @return array<string, mixed>|null
     */
    private static function pick(RangeStudy $study, Collection $scenarios, bool $painted, array $used): ?array
    {
        foreach ($scenarios as $scenario) {
            $hand = self::pickHand($scenario, $painted, $used);

            if ($hand === null) {
                continue;
            }

            $options = self::options($scenario);
            $correct = self::correctAction($scenario, $hand);

            if (! in_array($correct, array_column($options, 'key'), true)) {
                continue;
            }

            return [
                'scenario_id' => $scenario->id,
                'group' => $scenario->group_label,
                'row' => $scenario->row_label,
                'button' => $scenario->button_label,
                'row_color' => $study->rowColor($scenario->row_label),
                'hand' => $hand,
                'cards' => self::cards($hand),
                'options' => $options,
                'correct' => $correct,
            ];
        }

        return null;
    }

    /**
     * A hand not used yet. A painted hand is drawn by action first and hand
     * second, so a rare action (a 4-bet) comes up as often as a common one
     * instead of in proportion to how many hands the chart gives it.
     *
     * @param  array<string, true>  $used
     */
    private static function pickHand(RangeScenario $scenario, bool $painted, array $used): ?string
    {
        $isFree = fn (string $hand) => ! isset($used[$scenario->id.':'.$hand]);
        $all = PreflopGrid::hands();

        if (! $painted) {
            $unpainted = array_values(array_filter(array_diff($all, array_keys($scenario->combos)), $isFree));

            return $unpainted === [] ? null : $unpainted[array_rand($unpainted)];
        }

        $byAction = [];
        foreach ($all as $hand) {
            if (isset($scenario->combos[$hand]) && $isFree($hand)) {
                $byAction[$scenario->combos[$hand]][] = $hand;
            }
        }

        if ($byAction === []) {
            return null;
        }

        $hands = $byAction[array_rand($byAction)];

        return $hands[array_rand($hands)];
    }

    /**
     * Two cards for a hand code: a pair gets two suits, "s" shares one, "o" has two.
     *
     * @return list<array{rank: string, suit: string, red: bool}>
     */
    private static function cards(string $hand): array
    {
        $suits = self::SUITS;
        shuffle($suits);

        $first = $suits[0];
        $second = strlen($hand) === 3 && $hand[2] === 's' ? $suits[0] : $suits[1];

        return [
            ['rank' => self::rank($hand[0]), 'suit' => $first['symbol'], 'red' => $first['red']],
            ['rank' => self::rank($hand[1]), 'suit' => $second['symbol'], 'red' => $second['red']],
        ];
    }

    private static function rank(string $code): string
    {
        return $code === 'T' ? '10' : $code;
    }
}
