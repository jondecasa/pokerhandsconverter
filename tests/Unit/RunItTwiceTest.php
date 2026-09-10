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

    private function split(): CoinPokerConverter
    {
        return new CoinPokerConverter(new ConverterOptions(runItTwiceMode: 'split'));
    }

    // ------------------------------------------------------------------ keep (default)

    #[Test]
    public function keep_mode_normalises_a_run_it_twice_hand_to_one_native_hand(): void
    {
        $result = (new CoinPokerConverter)->convert($this->fixture());

        $this->assertSame(1, $result->handCount);
        $this->assertStringNotContainsString('-1:', $result->output);
        // both boards, ordinal prefix dropped, padding trimmed
        $this->assertStringContainsString('Board [7c 2d 9s Kh 4c]', $result->output);
        $this->assertStringContainsString('Board [7c 2d 9s Jd Qs]', $result->output);
        // the real total pot is kept (the tracker splits it itself for RIT)
        $this->assertStringContainsString('Total pot $20 | Rake $0', $result->output);
        $this->assertStringContainsString('Hand was run twice', $result->output);
        $this->assertTrue($result->hasWarnings());
    }

    // ------------------------------------------------------------------ split (opt-in)

    #[Test]
    public function split_mode_makes_one_hand_per_board(): void
    {
        $result = $this->split()->convert($this->fixture());

        $this->assertSame(2, $result->handCount);
        $this->assertStringContainsString('CoinPoker Hand #130114299001-1:', $result->output);
        $this->assertStringContainsString('CoinPoker Hand #130114299001-2:', $result->output);
    }

    #[Test]
    public function each_split_hand_carries_one_board_and_its_pot(): void
    {
        $out = $this->split()->convert($this->fixture())->output;
        [$one, $two] = preg_split('/\n{2,}/', trim($out));

        $this->assertStringContainsString('*** TURN *** [7c 2d 9s] [Kh]', $one);
        $this->assertStringContainsString('Board [7c 2d 9s Kh 4c]', $one);
        $this->assertStringContainsString('Total pot $10 | Rake $0', $one);
        $this->assertStringContainsString('bravo collected $10 from pot', $one);
        $this->assertStringContainsString('Seat 3: bravo (big blind) showed [As Ah] and won ($10)', $one);
        $this->assertStringContainsString('Seat 1: alpha (small blind) showed [Ks Kd] and lost with a pair of Kings', $one);
        $this->assertStringNotContainsString('FIRST', $one);
        $this->assertStringNotContainsString('Jd Qs', $one);

        $this->assertStringContainsString('*** TURN *** [7c 2d 9s] [Jd]', $two);
        $this->assertStringContainsString('Board [7c 2d 9s Jd Qs]', $two);
        $this->assertStringContainsString('alpha collected $10 from pot', $two);
        $this->assertStringContainsString('Seat 1: alpha (small blind) showed [Ks Kd] and won ($10)', $two);
    }

    #[Test]
    public function split_hands_still_get_the_normal_cleanups(): void
    {
        $out = $this->split()->convert($this->fixture())->output;

        $this->assertStringContainsString("Hold'em No Limit ($0.05/$0.10 USD)", $out);
        $this->assertStringNotContainsString('₮', $out);
        $this->assertStringNotContainsString('NLH', $out);
        $this->assertStringNotContainsString('Hand was run twice', $out);
        $this->assertStringNotContainsString('Dealt to alpha', $out);
        $this->assertStringContainsString('Dealt to bravo [As Ah]', $out);
        $this->assertSame(2, substr_count($out, '*** SHOW DOWN ***'));
    }
}
