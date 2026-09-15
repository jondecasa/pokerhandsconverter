<?php

namespace Tests\Unit;

use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CoinPokerConverterTest extends TestCase
{
    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../Fixtures/'.$name);
    }

    /** Compare ignoring trailing whitespace and blank-line runs. */
    private function normalise(string $text): string
    {
        $lines = array_map('rtrim', explode("\n", str_replace(["\r\n", "\r"], "\n", $text)));

        return trim(implode("\n", $lines));
    }

    #[Test]
    public function it_converts_a_real_cash_hand_to_the_expected_pokertracker_output(): void
    {
        $result = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'));

        $this->assertSame(1, $result->handCount);
        $this->assertSame(
            $this->normalise($this->fixture('expected/cash-pt4.txt')),
            $this->normalise($result->output),
        );
    }

    #[Test]
    public function it_rewrites_the_header_but_keeps_the_coinpoker_prefix(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString(
            "CoinPoker Hand #130114200045:  Hold'em No Limit (\$0.01/\$0.02 USD) - 2026/09/09 12:01:21 CET [2026/09/09 6:01:21 ET]",
            $out,
        );
        $this->assertStringNotContainsString('PokerStars', $out);
        $this->assertStringNotContainsString('₮', $out);
        $this->assertStringNotContainsString('NLH', $out);
    }

    #[Test]
    public function it_drops_the_per_player_dealt_to_lines_but_keeps_the_hero_cards(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('Dealt to Hero [Th 2s]', $out);
        $this->assertStringNotContainsString("Dealt to 3d1b2c99\n", $out);
        $this->assertStringNotContainsString('Dealt to ef025952', $out);
        $this->assertSame(1, substr_count($out, 'Dealt to '));
    }

    #[Test]
    public function it_converts_the_return_line_to_an_uncalled_bet(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('Uncalled bet ($0.04) returned to 3d2ba04f', $out);
        $this->assertStringNotContainsString('RETURN', $out);
    }

    #[Test]
    public function it_removes_coinpoker_only_summary_lines(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringNotContainsString('Hand was run once', $out);
        $this->assertStringNotContainsString('Board [', $out);          // empty board dropped entirely
        $this->assertStringNotContainsString('Game ended:', $out);
    }

    #[Test]
    public function it_drops_a_showdown_section_when_nobody_shows(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringNotContainsString('SHOWDOWN', $out);
        $this->assertStringNotContainsString('SHOW DOWN', $out);
        $this->assertStringContainsString('3d2ba04f collected $0.05 from pot', $out);
    }

    #[Test]
    public function it_adds_position_tags_and_fixes_won_to_collected_in_the_summary(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('Seat 3: 3d2ba04f (button) collected ($0.05)', $out);
        $this->assertStringContainsString('Seat 4: d92d5781 (small blind) folded before Flop', $out);
        $this->assertStringContainsString('Seat 5: Hero (big blind) folded before Flop', $out);
        $this->assertStringNotContainsString("(small blind) folded before Flop (didn't bet)", $out);
        $this->assertStringContainsString("Seat 6: ef025952 folded before Flop (didn't bet)", $out);
        $this->assertStringNotContainsString(' won (', $out);
    }

    #[Test]
    public function converting_the_output_again_is_stable(): void
    {
        $converter = new CoinPokerConverter;
        $once = $converter->convert($this->fixture('coinpoker-cash.txt'))->output;
        $twice = $converter->convert($once)->output;

        $this->assertSame($this->normalise($once), $this->normalise($twice));
    }

    #[Test]
    public function timezone_keep_mode_leaves_the_source_time_untouched(): void
    {
        $out = (new CoinPokerConverter(new ConverterOptions(timezoneMode: 'keep')))
            ->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('2026/09/09 12:01:21 CEST', $out);
        $this->assertStringNotContainsString('[', explode("\n", $out)[0]);
    }

    #[Test]
    public function timezone_et_mode_emits_a_single_eastern_stamp(): void
    {
        $out = (new CoinPokerConverter(new ConverterOptions(timezoneMode: 'et')))
            ->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString(' - 2026/09/09 6:01:21 ET', $out);
        $this->assertStringNotContainsString('CET', $out);
    }

    #[Test]
    public function tournament_hands_keep_bare_chip_amounts(): void
    {
        $result = (new CoinPokerConverter)->convert($this->fixture('coinpoker-tournament.txt'));
        $out = $result->output;

        $this->assertStringContainsString('CoinPoker Hand #130114300001: Tournament #55012,', $out);
        $this->assertStringContainsString('Hero: posts small blind 75', $out);
        $this->assertStringContainsString('Seat 1: north (3000 in chips)', $out);
        $this->assertStringContainsString('Hero: raises 225 to 375', $out);
        $this->assertStringContainsString('Uncalled bet (225) returned to Hero', $out);
        $this->assertStringContainsString('Total pot 825 | Rake 0', $out);
        $this->assertStringContainsString('Seat 4: Hero (button) collected (825)', $out);
        $this->assertStringContainsString('Dealt to Hero [Ad Qs]', $out);
        $this->assertSame('Tournament', $result->hands[0]->format);
    }

    #[Test]
    public function it_maps_game_codes(): void
    {
        $hand = "CoinPoker Hand #9: PLO (₮0.05/₮0.10) 2026/01/02 03:04:05 CET\n"
            ."Table 'x' 6-max Seat #1 is the button\n"
            ."Seat 1: a (₮10 in chips)\n"
            ."Seat 2: b (₮10 in chips)\n"
            .'*** SUMMARY ***';

        $out = (new CoinPokerConverter)->convert($hand)->output;

        $this->assertStringContainsString('Omaha Pot Limit ($0.05/$0.10 USD)', $out);
    }

    #[Test]
    public function a_splash_fee_is_folded_into_the_rake_pokertracker_understands(): void
    {
        // Real CoinPoker hands sometimes tack on a "Splash Fee" alongside the
        // rake. PokerTracker has no concept of it — left in place it stops PT4
        // from reading the rake at all, so pot != collected + rake and the hand
        // is rejected with "Invalid pot size".
        $hand = "CoinPoker Hand #1: NLH (₮0.10/₮0.25) 2026/01/02 03:04:05 CET\n"
            ."Table 'x' 6-max Seat #1 is the button\n"
            ."Seat 1: a (₮10 in chips)\n"
            ."Seat 2: b (₮10 in chips)\n"
            ."a: posts small blind ₮0.10\n"
            ."b: posts big blind ₮0.25\n"
            ."*** HOLE CARDS ***\n"
            ."a: calls ₮0.15\n"
            ."b: checks\n"
            ."*** SHOWDOWN ***\n"
            ."b collected ₮0.47 from pot\n"
            ."*** SUMMARY ***\n"
            ."Total pot ₮0.50 | Rake ₮0.02 | Splash Fee ₮0.01\n"
            .'Board [ ]';

        $out = (new CoinPokerConverter)->convert($hand)->output;

        $this->assertStringContainsString('Total pot $0.50 | Rake $0.03', $out);
        $this->assertStringNotContainsString('Splash Fee', $out);
    }

    #[Test]
    public function it_warns_about_run_it_twice_and_keeps_coinpokers_native_markers(): void
    {
        $hand = "CoinPoker Hand #7: NLH (₮1/₮2) 2026/01/02 03:04:05 CET\n"
            ."Table 'x' 6-max Seat #1 is the button\n"
            ."Seat 1: a (₮200 in chips)\n"
            ."*** FIRST FLOP *** [As Kd 2c]\n"
            ."*** SECOND FLOP *** [Ts 8d 4c]\n"
            ."*** SUMMARY ***\n"
            ."Total pot ₮400 | Rake ₮0\n"
            ."Hand was run with two boards\n"
            ."FIRST Board [ As Kd 2c 7h 9s ]\n"
            .'SECOND Board [ Ts 8d 4c Qh 3s ]';

        $result = (new CoinPokerConverter)->convert($hand);

        $this->assertTrue($result->hasWarnings());
        $this->assertStringContainsString('Run-it-twice', $result->warnings[0]['message']);
        // kept verbatim so PokerTracker's native CoinPoker profile splits the pot
        $this->assertStringContainsString('Total pot $400 | Rake $0', $result->output);
        $this->assertStringContainsString('Hand was run with two boards', $result->output);
        $this->assertStringContainsString('FIRST Board [As Kd 2c 7h 9s]', $result->output);
        $this->assertStringContainsString('SECOND Board [Ts 8d 4c Qh 3s]', $result->output);
    }

    #[Test]
    public function it_skips_non_hand_blocks_with_a_warning(): void
    {
        $input = "Session export 2026\n\n".$this->fixture('coinpoker-cash.txt');

        $result = (new CoinPokerConverter)->convert($input);

        $this->assertSame(1, $result->handCount);
        $this->assertNotEmpty($result->warnings);
    }

    #[Test]
    public function it_replaces_hero_with_the_configured_screen_name(): void
    {
        $out = (new CoinPokerConverter(new ConverterOptions(heroName: 'batu157')))
            ->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('Seat 5: batu157 ($2 in chips)', $out);
        $this->assertStringContainsString('Dealt to batu157 [Th 2s]', $out);
        $this->assertStringContainsString('batu157: posts big blind $0.02', $out);
        $this->assertStringNotContainsString('Hero', $out);
    }

    #[Test]
    public function it_keeps_hero_when_no_screen_name_is_set(): void
    {
        $out = (new CoinPokerConverter)->convert($this->fixture('coinpoker-cash.txt'))->output;

        $this->assertStringContainsString('Dealt to Hero [Th 2s]', $out);
    }

    #[Test]
    public function it_reports_the_highest_cash_stake_in_the_file(): void
    {
        $result = (new CoinPokerConverter)->convert(
            file_get_contents(__DIR__.'/../../samples/coinpoker-nl50-example.txt')
        );

        $this->assertSame('NL50', $result->maxCashStakeLevel());
        $this->assertEqualsWithDelta(0.50, $result->maxCashBigBlind(), 0.0001);
    }
}
