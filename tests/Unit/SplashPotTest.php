<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Uses real CoinPoker splash-pot / mega-splash exports. */
class SplashPotTest extends TestCase
{
    private function convert()
    {
        return (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../Fixtures/coinpoker-splash-pot.txt')
        );
    }

    #[Test]
    public function it_detects_splash_and_mega_splash_pots(): void
    {
        $result = $this->convert();

        $this->assertSame(2, $result->handCount);
        $this->assertSame(2, $result->splashPotCount());
        $this->assertTrue($result->hands[0]->splashPot);
        $this->assertTrue($result->hands[1]->splashPot);
        $this->assertFalse($result->hands[0]->bombPot);
    }

    #[Test]
    public function it_drops_the_splash_lines_and_trims_the_board_padding(): void
    {
        $out = $this->convert()->output;

        $this->assertStringNotContainsString('SPLASH dropped', $out);
        $this->assertStringNotContainsString('MEGA SPLASH', $out);
        $this->assertStringContainsString('Board [Qh Ks Jh 7d Kh]', $out);
        $this->assertStringContainsString('Board [7s 2c 9d]', $out);
        $this->assertStringNotContainsString('Board [ ', $out);
        // untouched street boards keep their shape
        $this->assertStringContainsString('*** RIVER *** [Qh Ks Jh 7d] [Kh]', $out);
        $this->assertStringNotContainsString('₮', $out);
    }
}
