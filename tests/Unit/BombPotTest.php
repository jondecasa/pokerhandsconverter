<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Uses a CoinPoker "NLH BombPot" export. */
class BombPotTest extends TestCase
{
    private function convert()
    {
        return (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../Fixtures/coinpoker-bomb-pot.txt')
        );
    }

    #[Test]
    public function it_recognises_the_bombpot_game_tag_without_warning(): void
    {
        $result = $this->convert();

        $this->assertSame(1, $result->handCount);
        $this->assertSame(1, $result->bombPotCount());
        $this->assertTrue($result->hands[0]->bombPot);

        // No "unknown game code" warning for "NLH BombPot".
        foreach ($result->warnings as $w) {
            $this->assertStringNotContainsStringIgnoringCase('unknown game code', $w['message']);
        }
    }

    #[Test]
    public function the_game_is_normalised_to_holdem_no_limit(): void
    {
        $out = $this->convert()->output;

        $this->assertStringContainsString("Hold'em No Limit ($0.01/$0.02 USD)", $out);
        $this->assertStringNotContainsString('NLH BombPot', $out);
        $this->assertStringNotContainsString('BombPot', $out);
    }
}
