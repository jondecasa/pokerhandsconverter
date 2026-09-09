<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CoinPokerConverterTest extends TestCase
{
    private function cash(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/coinpoker-cash.txt');
    }

    private function tournament(): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/coinpoker-tournament.txt');
    }

    #[Test]
    public function it_rewrites_the_room_prefix_and_counts_hands(): void
    {
        $result = (new CoinPokerConverter)->convert($this->cash());

        $this->assertSame(2, $result->handCount);
        $this->assertStringContainsString('PokerStars Hand #2100000001:', $result->output);
        $this->assertStringNotContainsString('CoinPoker Hand #', $result->output);
    }

    #[Test]
    public function it_normalises_currency_code_and_timezone_in_the_header(): void
    {
        $result = (new CoinPokerConverter)->convert($this->cash());

        $this->assertStringContainsString('($0.02/$0.05 USD) - 2024/03/10 18:30:12 ET', $result->output);
        $this->assertStringNotContainsString('USDT', $result->output);
        $this->assertStringNotContainsString(' UTC', $result->output);
    }

    #[Test]
    public function it_prefixes_currency_symbols_to_bare_cash_amounts(): void
    {
        $out = (new CoinPokerConverter)->convert($this->cash())->output;

        $this->assertStringContainsString('bravo: posts small blind $0.02', $out);
        $this->assertStringContainsString('Hero: posts big blind $0.05', $out);
        $this->assertStringContainsString('Seat 1: alpha ($5 in chips)', $out);
        $this->assertStringContainsString('alpha: raises $0.10 to $0.15', $out);
        $this->assertStringContainsString('alpha: bets $0.20', $out);
        $this->assertStringContainsString('Hero: calls $0.10', $out);
        $this->assertStringContainsString('Uncalled bet ($0.35) returned to Hero', $out);
        $this->assertStringContainsString('Hero collected $0.70 from pot', $out);
        $this->assertStringContainsString('Total pot $0.72 | Rake $0.02', $out);
        $this->assertStringContainsString('Seat 3: Hero (big blind) collected ($0.70)', $out);
    }

    #[Test]
    public function it_does_not_double_prefix_amounts_that_already_have_a_symbol(): void
    {
        $out = (new CoinPokerConverter)->convert($this->cash())->output;

        $this->assertStringNotContainsString('$$', $out);
    }

    #[Test]
    public function converting_the_output_again_is_stable(): void
    {
        $converter = new CoinPokerConverter;
        $once = $converter->convert($this->cash())->output;
        $twice = $converter->convert($once)->output;

        $this->assertSame($once, $twice);
    }

    #[Test]
    public function tournament_chip_amounts_stay_bare(): void
    {
        $out = (new CoinPokerConverter)->convert($this->tournament())->output;

        $this->assertStringContainsString('PokerStars Hand #2200000001: Tournament #9911002, $10+$1 USD Hold\'em No Limit - Level V (75/150) - 2024/03/11 20:05:00 ET', $out);
        $this->assertStringContainsString('Hero: posts small blind 75', $out);
        $this->assertStringContainsString('west: posts big blind 150', $out);
        $this->assertStringContainsString('Seat 1: north (3000 in chips)', $out);
        $this->assertStringContainsString('Hero: raises 225 to 375', $out);
        $this->assertStringContainsString('Total pot 825 | Rake 0', $out);
        $this->assertStringNotContainsString('$', $out === null ? '' : substr($out, strpos($out, '*** HOLE CARDS ***')));
    }

    #[Test]
    public function it_builds_per_hand_summaries(): void
    {
        $result = (new CoinPokerConverter)->convert($this->cash());

        $first = $result->hands[0];
        $this->assertSame('2100000001', $first->handId);
        $this->assertSame('Cash', $first->format);
        $this->assertSame("Hold'em No Limit", $first->game);
        $this->assertSame('Saturn', $first->table);
        $this->assertSame(6, $first->maxSeats);
        $this->assertSame('Hero', $first->hero);
        $this->assertSame(['Cash' => 2], $result->formatBreakdown());
    }

    #[Test]
    public function tournament_summary_is_detected(): void
    {
        $result = (new CoinPokerConverter)->convert($this->tournament());

        $this->assertSame('Tournament', $result->hands[0]->format);
        $this->assertSame(9, $result->hands[0]->maxSeats);
    }

    #[Test]
    public function timezone_keep_mode_leaves_utc_untouched(): void
    {
        $options = new ConverterOptions(timezoneMode: 'keep');
        $out = (new CoinPokerConverter($options))->convert($this->cash())->output;

        $this->assertStringContainsString('2024/03/10 18:30:12 UTC', $out);
    }

    #[Test]
    public function timezone_convert_mode_shifts_the_printed_time(): void
    {
        $options = new ConverterOptions(timezoneMode: 'convert', offsetHours: -5);
        $out = (new CoinPokerConverter($options))->convert($this->cash())->output;

        $this->assertStringContainsString('2024/03/10 13:30:12 ET', $out);
    }

    #[Test]
    public function it_warns_about_run_it_twice_boards(): void
    {
        $hand = <<<'TXT'
        CoinPoker Hand #3: Hold'em No Limit ($1/$2 USDT) - 2024/03/12 10:00:00 UTC
        Table 'RIT' 6-max Seat #1 is the button
        Seat 1: a ($200 in chips)
        Seat 2: b ($200 in chips)
        *** FIRST FLOP *** [As Kd 2c]
        *** SECOND FLOP *** [7h 7d 7s]
        *** SUMMARY ***
        Total pot $400 | Rake $0
        TXT;

        $result = (new CoinPokerConverter)->convert($hand);

        $this->assertTrue($result->hasWarnings());
        $this->assertStringContainsString('Run-it-twice', $result->warnings[0]['message']);
    }

    #[Test]
    public function it_skips_non_hand_blocks_with_a_warning(): void
    {
        $input = "Some export header line\n\n".$this->tournament();

        $result = (new CoinPokerConverter)->convert($input);

        $this->assertSame(1, $result->handCount);
        $this->assertNotEmpty($result->warnings);
    }
}
