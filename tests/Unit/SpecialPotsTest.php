<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpecialPotsTest extends TestCase
{
    private function convertFixture()
    {
        return (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../Fixtures/coinpoker-special-pots.txt')
        );
    }

    #[Test]
    public function it_counts_hands_splash_pots_and_bomb_pots(): void
    {
        $result = $this->convertFixture();

        $this->assertSame(3, $result->handCount);
        $this->assertSame(1, $result->bombPotCount());   // the antes-only, no-blinds hand
        $this->assertSame(1, $result->splashPotCount());  // the "Splash pot: ... added" hand
    }

    #[Test]
    public function the_flags_land_on_the_right_hands(): void
    {
        $hands = $this->convertFixture()->hands;

        $this->assertTrue($hands[0]->bombPot);
        $this->assertFalse($hands[0]->splashPot);

        $this->assertTrue($hands[1]->splashPot);
        $this->assertFalse($hands[1]->bombPot);

        $this->assertFalse($hands[2]->bombPot);
        $this->assertFalse($hands[2]->splashPot);
    }
}
