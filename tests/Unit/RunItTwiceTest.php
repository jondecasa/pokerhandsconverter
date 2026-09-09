<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RunItTwiceTest extends TestCase
{
    private function fixture(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/coinpoker-run-it-twice.txt');
    }

    #[Test]
    public function it_splits_a_run_it_twice_hand_into_one_hand_per_board(): void
    {
        $result = (new CoinPokerConverter)->convert($this->fixture());

        $this->assertSame(2, $result->handCount);
        $this->assertStringContainsString('CoinPoker Hand #130114299001-1:', $result->output);
        $this->assertStringContainsString('CoinPoker Hand #130114299001-2:', $result->output);
        $this->assertTrue($result->hasWarnings());
    }

    #[Test]
    public function each_split_hand_carries_one_board_and_half_the_pot(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture())->output;
        [$one, $two] = preg_split('/\n{2,}/', trim($out));

        // First run: the Kh/4c board, bravo wins the 9.60 half.
        $this->assertStringContainsString('*** TURN *** [7c 2d 9s] [Kh]', $one);
        $this->assertStringContainsString('Board [7c 2d 9s Kh 4c]', $one);
        $this->assertStringContainsString('Total pot $9.60 | Rake $0', $one);
        $this->assertStringContainsString('bravo collected $9.60 from pot', $one);
        $this->assertStringContainsString('Seat 3: bravo (big blind) showed [As Ah] and won ($9.60)', $one);
        $this->assertStringContainsString('Seat 1: alpha (small blind) showed [Ks Kd] and lost with a pair of Kings', $one);
        $this->assertStringNotContainsString('FIRST', $one);
        $this->assertStringNotContainsString('Jd Qs', $one);

        // Second run: the Jd/Qs board, alpha wins the other half.
        $this->assertStringContainsString('*** TURN *** [7c 2d 9s] [Jd]', $two);
        $this->assertStringContainsString('Board [7c 2d 9s Jd Qs]', $two);
        $this->assertStringContainsString('alpha collected $9.60 from pot', $two);
        $this->assertStringContainsString('Seat 1: alpha (small blind) showed [Ks Kd] and won ($9.60)', $two);
    }

    #[Test]
    public function split_hands_still_get_the_normal_cleanups(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture())->output;

        $this->assertStringContainsString("Hold'em No Limit ($0.05/$0.10 USD)", $out);
        $this->assertStringNotContainsString('₮', $out);
        $this->assertStringNotContainsString('NLH', $out);
        $this->assertStringNotContainsString('Hand was run twice', $out);
        $this->assertStringNotContainsString('Dealt to alpha', $out); // per-player noise gone
        $this->assertStringContainsString('Dealt to bravo [As Ah]', $out);
        $this->assertSame(2, substr_count($out, '*** SHOW DOWN ***'));
    }

    #[Test]
    public function keep_mode_leaves_a_single_hand_with_a_warning(): void
    {
        $result = (new CoinPokerConverter(new ConverterOptions(runItTwiceMode: 'keep')))
            ->convert($this->fixture());

        $this->assertSame(1, $result->handCount);
        $this->assertStringContainsString('Board [7c 2d 9s Kh 4c]', $result->output);
        $this->assertStringContainsString('Board [7c 2d 9s Jd Qs]', $result->output);
        $this->assertTrue($result->hasWarnings());
    }
}
