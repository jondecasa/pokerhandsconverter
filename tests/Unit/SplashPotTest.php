<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Uses a real CoinPoker splash-pot export. */
class SplashPotTest extends TestCase
{
    private function convert()
    {
        return (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../Fixtures/coinpoker-splash-pot.txt')
        );
    }

    #[Test]
    public function it_detects_a_real_splash_pot(): void
    {
        $result = $this->convert();

        $this->assertSame(1, $result->handCount);
        $this->assertSame(1, $result->splashPotCount());
        $this->assertTrue($result->hands[0]->splashPot);
        $this->assertFalse($result->hands[0]->bombPot);
    }

    #[Test]
    public function it_drops_the_splash_line_and_trims_the_board_padding(): void
    {
        $out = $this->convert()->output;

        $this->assertStringNotContainsString('SPLASH dropped', $out);
        $this->assertStringContainsString('Board [Qh Ks Jh 7d Kh]', $out);
        $this->assertStringNotContainsString('Board [ Qh', $out);
        // untouched street boards keep their shape
        $this->assertStringContainsString('*** RIVER *** [Qh Ks Jh 7d] [Kh]', $out);
        $this->assertStringNotContainsString('₮', $out);
    }
}
