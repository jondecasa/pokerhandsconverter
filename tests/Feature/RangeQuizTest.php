<?php

namespace Tests\Feature;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use App\Poker\PreflopGrid;
use App\Poker\RangeQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RangeQuizTest extends TestCase
{
    use RefreshDatabase;

    private const THREE_BET_LEGEND = [
        ['key' => 'fold', 'label' => 'Fold', 'color' => '#ef4444'],
        ['key' => 'call', 'label' => 'Call', 'color' => '#eab308'],
        ['key' => '4bf', 'label' => '4bet → Fold', 'color' => '#22c55e'],
        ['key' => '4bc', 'label' => '4bet → Call', 'color' => '#3b82f6'],
    ];

    private function scenario(RangeStudy $study, array $attributes = []): RangeScenario
    {
        return RangeScenario::factory()->for($study, 'study')->create($attributes + [
            'group_label' => 'vs 3bet',
            'row_label' => 'EP-MP',
            'button_label' => 'vs CO-BTN',
            'legend' => self::THREE_BET_LEGEND,
            'combos' => ['99' => 'call', 'AA' => '4bc', 'KK' => '4bc', 'AKs' => '4bf', 'QQ' => 'call', 'JJ' => 'call', 'TT' => 'call', 'AQs' => 'call'],
        ]);
    }

    #[Test]
    public function it_builds_twenty_questions_without_repeating_a_spot(): void
    {
        $study = RangeStudy::factory()->create();
        $this->scenario($study);
        $this->scenario($study, ['row_label' => 'CO', 'button_label' => 'vs BTN']);

        $questions = RangeQuiz::questions($study);

        $this->assertCount(20, $questions);
        $spots = array_map(fn (array $q) => $q['scenario_id'].':'.$q['hand'], $questions);
        $this->assertCount(20, array_unique($spots));
        $this->assertSame(range(1, 20), array_column($questions, 'id'));
    }

    #[Test]
    public function the_correct_answer_is_what_the_chart_paints_and_fold_for_an_unpainted_hand(): void
    {
        $study = RangeStudy::factory()->create();
        $scenario = $this->scenario($study);

        foreach (RangeQuiz::questions($study) as $question) {
            $expected = $scenario->combos[$question['hand']] ?? 'fold';
            $this->assertSame($expected, $question['correct'], "wrong answer for {$question['hand']}");
        }
    }

    #[Test]
    public function an_unpainted_hand_such_as_85o_is_a_fold(): void
    {
        $scenario = $this->scenario(RangeStudy::factory()->create());

        $this->assertSame('fold', RangeQuiz::correctAction($scenario, '85o'));
        $this->assertSame('call', RangeQuiz::correctAction($scenario, '99'));
    }

    #[Test]
    public function the_answer_buttons_are_the_scenarios_own_actions(): void
    {
        $study = RangeStudy::factory()->create();
        $this->scenario($study);

        $question = RangeQuiz::questions($study)[0];

        $this->assertSame(['fold', 'call', '4bf', '4bc'], array_column($question['options'], 'key'));
        $this->assertContains($question['correct'], array_column($question['options'], 'key'));
    }

    #[Test]
    public function fold_is_added_as_an_option_when_the_chart_only_paints_raises(): void
    {
        $study = RangeStudy::factory()->create();
        $this->scenario($study, [
            'group_label' => 'OR',
            'legend' => [['key' => 'or', 'label' => 'OR', 'color' => '#3b82f6']],
            'combos' => ['AA' => 'or', 'KK' => 'or'],
        ]);

        foreach (RangeQuiz::questions($study) as $question) {
            $this->assertSame(['fold', 'or'], array_column($question['options'], 'key'));
            $this->assertContains($question['correct'], ['fold', 'or']);
        }
    }

    #[Test]
    public function charts_that_are_not_in_the_quiz_or_have_no_painted_hands_are_left_out(): void
    {
        $study = RangeStudy::factory()->create();
        $included = $this->scenario($study);
        $this->scenario($study, ['group_label' => 'Rol', 'quiz_enabled' => false]);
        $this->scenario($study, ['group_label' => 'Empty', 'combos' => []]);

        $groups = array_unique(array_column(RangeQuiz::questions($study), 'group'));

        $this->assertSame(['vs 3bet'], array_values($groups));
        $this->assertSame([$included->id], RangeQuiz::quizScenarios($study)->pluck('id')->all());
    }

    #[Test]
    public function most_questions_use_a_hand_the_chart_paints(): void
    {
        $study = RangeStudy::factory()->create();
        $scenario = $this->scenario($study);

        $painted = collect(RangeQuiz::questions($study))
            ->filter(fn (array $q) => array_key_exists($q['hand'], $scenario->combos))
            ->count();

        // 14 painted slots requested, but only 8 painted hands exist: all 8 are used.
        $this->assertSame(8, $painted);
    }

    #[Test]
    public function with_plenty_of_painted_hands_the_mix_is_seventy_percent_painted(): void
    {
        $study = RangeStudy::factory()->create();
        $combos = collect(PreflopGrid::hands())->take(60)->mapWithKeys(fn (string $hand) => [$hand => 'call'])->all();
        $scenario = $this->scenario($study, ['combos' => $combos]);

        $painted = collect(RangeQuiz::questions($study))
            ->filter(fn (array $q) => array_key_exists($q['hand'], $scenario->combos))
            ->count();

        $this->assertSame(14, $painted);
    }

    #[Test]
    public function a_rare_action_comes_up_as_often_as_a_common_one(): void
    {
        $study = RangeStudy::factory()->create();
        $combos = collect(PreflopGrid::hands())->take(40)->mapWithKeys(fn (string $hand) => [$hand => 'call'])->all();
        $combos['72o'] = '4bc';
        $combos['83o'] = '4bc';
        $this->scenario($study, ['combos' => $combos]);

        $rare = 0;
        for ($i = 0; $i < 30; $i++) {
            $rare += collect(RangeQuiz::questions($study))->where('correct', '4bc')->count();
        }

        // Two 4bet hands out of 42 painted: chosen by hand they'd average under one per
        // quiz (about 21 here); chosen by action they fill both slots almost every time.
        $this->assertGreaterThanOrEqual(45, $rare);
    }

    #[Test]
    public function a_study_without_quizzable_scenarios_has_no_questions(): void
    {
        $this->assertSame([], RangeQuiz::questions(RangeStudy::factory()->create()));
    }

    #[Test]
    public function cards_match_the_hand_type(): void
    {
        $study = RangeStudy::factory()->create();
        $this->scenario($study, ['combos' => ['99' => 'call', 'AKs' => 'call', 'AKo' => 'call', 'T9s' => 'call']]);

        foreach (RangeQuiz::questions($study) as $q) {
            [$a, $b] = $q['cards'];
            $hand = $q['hand'];

            if (strlen($hand) === 3 && $hand[2] === 's') {
                $this->assertSame($a['suit'], $b['suit'], "$hand should be suited");
            } else {
                $this->assertNotSame($a['suit'], $b['suit'], "$hand should have two suits");
            }

            $this->assertSame($hand[0] === 'T' ? '10' : $hand[0], $a['rank']);
            $this->assertSame($hand[1] === 'T' ? '10' : $hand[1], $b['rank']);
        }
    }
}
