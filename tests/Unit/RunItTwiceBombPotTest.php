<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Real CoinPoker run-it-twice bomb pot (action between boards, two showdowns). */
class RunItTwiceBombPotTest extends TestCase
{
    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/coinpoker-rit-bombpot.txt');
    }

    private function runs(): array
    {
        $out = (new CoinPokerConverter(new ConverterOptions(runItTwiceMode: 'split')))
            ->convert($this->fixture())->output;

        return preg_split('/\n{2,}/', trim($out));
    }

    #[Test]
    public function keep_mode_normalises_it_to_one_native_run_it_twice_hand(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture())->output;

        $this->assertStringNotContainsString('-1:', $out);
        $this->assertStringContainsString("Hold'em No Limit (\$0.01/\$0.02/\$0.04 USD)", $out);
        $this->assertStringContainsString('*** FIRST FLOP *** [8h 4d 9c]', $out);
        $this->assertStringContainsString('*** SECOND FLOP *** [Jc As 2d]', $out);
        $this->assertStringContainsString('*** FIRST SHOW DOWN ***', $out);
        $this->assertStringContainsString('*** SECOND SHOW DOWN ***', $out);
        $this->assertStringContainsString('Total pot $0.79 | Rake $0.04', $out);   // real total kept
        $this->assertStringContainsString('Hand was run twice', $out);
        $this->assertStringContainsString('Board [8h 4d 9c 6c Ks]', $out);
        $this->assertStringContainsString('Board [Jc As 2d 8c 2c]', $out);
        $this->assertStringNotContainsString('FIRST Board', $out);
        $this->assertStringNotContainsString('SHOWDOWN', $out); // all spaced now
        $this->assertStringNotContainsString('BombPot', $out);
    }

    #[Test]
    public function it_splits_into_one_hand_per_board(): void
    {
        [$one, $two] = $this->runs();

        $this->assertStringContainsString('CoinPoker Hand #130049700216-1:', $one);
        $this->assertStringContainsString('CoinPoker Hand #130049700216-2:', $two);
        // "NLH BombPot" -> "Hold'em No Limit", 3-value stakes kept verbatim
        $this->assertStringContainsString("Hold'em No Limit (\$0.01/\$0.02/\$0.04 USD)", $one);
        $this->assertStringNotContainsString('BombPot', $one);
    }

    #[Test]
    public function each_run_gets_its_board_the_shared_action_and_its_showdown(): void
    {
        [$one, $two] = $this->runs();

        // Run 1 = the 8h4d9c / 6c / Ks board
        $this->assertStringContainsString('*** FLOP *** [8h 4d 9c]', $one);
        $this->assertStringContainsString('*** TURN *** [8h 4d 9c] [6c]', $one);
        $this->assertStringContainsString('*** RIVER *** [8h 4d 9c 6c] [Ks]', $one);
        $this->assertStringContainsString('Board [8h 4d 9c 6c Ks]', $one);
        $this->assertStringNotContainsString('Jc As 2d', $one);
        // shared betting is duplicated into the run
        $this->assertStringContainsString('5ffd16b1: bets $0.13', $one);
        // this run's showdown + collected
        $this->assertStringContainsString('9e6eae1d collected $0.37 from pot', $one);

        // Run 2 = the JcAs2d / 8c / 2c board
        $this->assertStringContainsString('*** RIVER *** [Jc As 2d 8c] [2c]', $two);
        $this->assertStringContainsString('Board [Jc As 2d 8c 2c]', $two);
        $this->assertStringContainsString('5ffd16b1 collected $0.38 from pot', $two);
    }

    #[Test]
    public function the_pot_per_run_is_that_runs_collected_plus_half_the_rake(): void
    {
        [$one, $two] = $this->runs();

        $this->assertStringContainsString('Total pot $0.39 | Rake $0.02', $one);  // 0.37 + 0.02
        $this->assertStringContainsString('Total pot $0.40 | Rake $0.02', $two);  // 0.38 + 0.02
    }

    #[Test]
    public function the_combined_seat_summary_line_is_split_per_run(): void
    {
        [$one, $two] = $this->runs();

        // 5ffd16b1: lost run 1, won run 2
        $this->assertStringContainsString('Seat 1: 5ffd16b1 (button) showed [Ah 7s] and lost with High Card', $one);
        $this->assertStringContainsString('Seat 1: 5ffd16b1 (button) showed [Ah 7s] and won ($0.38) with Two Pair', $two);

        // 9e6eae1d: won run 1, lost run 2
        $this->assertStringContainsString('Seat 4: 9e6eae1d showed [4s 3h] and won ($0.37) with One Pair', $one);
        $this->assertStringContainsString('Seat 4: 9e6eae1d showed [4s 3h] and lost with One Pair', $two);

        // folds carry over unchanged
        $this->assertStringContainsString('Seat 3: cf0b34b3 folded on the Turn', $one);
        $this->assertStringContainsString('Seat 6: Hero folded on the Flop', $two);

        $this->assertStringNotContainsString('Hand was run with two boards', $one);
        $this->assertStringNotContainsString('Game ended:', $one);
    }
}
