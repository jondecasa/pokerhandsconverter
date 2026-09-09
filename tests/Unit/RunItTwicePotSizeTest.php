<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A run-it-twice bomb pot where one player wins both boards. CoinPoker's
 * "Total pot" is the grand total of both payouts + rake; PT4 checks the pot per
 * board, so the converter divides it by the board count.
 */
class RunItTwicePotSizeTest extends TestCase
{
    private function converted(): string
    {
        return (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../Fixtures/coinpoker-rit-bombpot-samewinner.txt')
        )->output;
    }

    #[Test]
    public function the_total_pot_line_is_stated_per_board(): void
    {
        $out = $this->converted();

        // CoinPoker said "Total pot ₮0.84 | Rake ₮0.04"; per board -> 0.42 / 0.02
        $this->assertStringContainsString('Total pot $0.42 | Rake $0.02', $out);
        $this->assertStringNotContainsString('Total pot $0.84', $out);
    }

    #[Test]
    public function a_no_showdown_double_win_seat_line_uses_collected_for_both(): void
    {
        $out = $this->converted();

        $this->assertStringContainsString('Seat 3: 63a59ceb collected ($0.40), and collected ($0.40)', $out);
        $this->assertStringNotContainsString('won (', $out);
    }

    #[Test]
    public function it_stays_one_hand_with_the_native_markers(): void
    {
        $out = $this->converted();

        $this->assertStringNotContainsString('#130049700252-1', $out);
        $this->assertStringContainsString('*** FIRST SHOW DOWN ***', $out);
        $this->assertStringContainsString('*** SECOND SHOW DOWN ***', $out);
        $this->assertStringContainsString('Hand was run twice', $out);
        $this->assertStringContainsString('Board [Qh Qs 9h 7c]', $out);
        $this->assertStringContainsString('Board [6d 6c 5c 3c]', $out);
        $this->assertStringContainsString('Uncalled bet ($0.44) returned to 63a59ceb', $out);
    }
}
