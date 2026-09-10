<?php

namespace App\Poker;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Cleans a CoinPoker hand-history file so PokerTracker 4 (and Hold'em Manager 3,
 * Hand2Note, ...) import it with their native CoinPoker profile.
 *
 * The "CoinPoker Hand #..." header prefix is preserved; only the parts that
 * break those parsers are fixed (game code, the ₮ sign, per-player "Dealt to"
 * lines, the RETURN line, "Hand was run once" / "Game ended:" noise, the
 * timezone stamp, and "won" -> "collected" plus position tags in the summary).
 *
 * Verified against real CoinPoker exports. The transformation per hand:
 *
 *   Header
 *     - "CoinPoker Hand #<id>: NLH (₮a/₮b) <date> CEST"
 *       becomes
 *       "CoinPoker Hand #<id>:  Hold'em No Limit ($a/$b USD) - <date> CET [<ET date/time> ET]"
 *     - "CoinPoker Hand #" prefix is preserved (native tracker import)
 *     - game abbreviation expanded (NLH -> Hold'em No Limit, PLO -> Omaha Pot Limit, ...)
 *     - tether sign ₮ -> $, currency code (USD) added inside the parenthesis
 *     - " - " inserted before the date; timezone rendered per ConverterOptions
 *
 *   Body
 *     - "₮" -> "$" everywhere; bare cash amounts get a symbol as a safety net
 *     - the per-player "Dealt to <name>" lines (no cards) are dropped; only
 *       "Dealt to Hero [Xx Yy]" is kept
 *     - "<player>: RETURN <amt>" -> "Uncalled bet ($amt) returned to <player>"
 *     - "*** SHOWDOWN ***" -> "*** SHOW DOWN ***", or dropped when nobody shows
 *
 *   Summary
 *     - CoinPoker-only lines removed: "Hand was run once", "Board [  ]" (empty),
 *       "Game ended: ..." / "Game started: ..."
 *     - "Seat N: NAME won (amt)" -> "Seat N: NAME (position) collected ($amt)"
 *     - button / small blind / big blind position tags inserted on seat lines;
 *       the misleading "(didn't bet)" is stripped from blind posters
 *
 * Anything ambiguous (run-it-twice, unknown game code, unknown timezone, stray
 * blocks) is recorded as a warning rather than throwing.
 */
class CoinPokerConverter
{
    /** CoinPoker game codes -> full game names PokerTracker 4 expects. */
    private const GAMES = [
        'NLH' => "Hold'em No Limit",
        'NLHE' => "Hold'em No Limit",
        'LHE' => "Hold'em Limit",
        'FLHE' => "Hold'em Limit",
        'PLH' => "Hold'em Pot Limit",
        'PLO' => 'Omaha Pot Limit',
        'PLO4' => 'Omaha Pot Limit',
        'PLO5' => '5 Card Omaha Pot Limit',
        'PLO6' => '6 Card Omaha Pot Limit',
        'NLO' => 'Omaha No Limit',
        'PLO8' => 'Omaha Hi/Lo Pot Limit',
        'PLO85' => '5 Card Omaha Hi/Lo Pot Limit',
    ];

    /** Source tz abbreviation -> hours to subtract to reach the "ET" stamp. */
    private const ET_OFFSETS = [
        'CET' => 6, 'CEST' => 6,
        'WET' => 5, 'WEST' => 5, 'GMT' => 5, 'UTC' => 5, 'BST' => 5,
        'EET' => 7, 'EEST' => 7,
        'MSK' => 8,
        'ET' => 0, 'EST' => 0, 'EDT' => 0,
    ];

    /** Daylight-saving abbreviation -> the standard label the format prints. */
    private const STD_LABELS = [
        'CEST' => 'CET', 'WEST' => 'WET', 'BST' => 'GMT', 'EEST' => 'EET',
        'EDT' => 'ET', 'EST' => 'ET', 'PDT' => 'PT', 'PST' => 'PT',
    ];

    public function __construct(
        private ConverterOptions $options = new ConverterOptions,
    ) {}

    public function convert(string $content): ConversionResult
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $blocks = preg_split('/\n[ \t]*\n+/', trim($content)) ?: [];

        $outHands = [];
        $summaries = [];
        $warnings = [];
        $index = 0;
        $excludedBombPots = 0;
        $excludedSplashPots = 0;

        foreach ($blocks as $block) {
            $block = trim($block, "\n");
            if ($block === '') {
                continue;
            }

            if (! preg_match('/^(?:CoinPoker|PokerStars)\s+Hand\s+#/i', $block)) {
                $warnings[] = ['hand' => null, 'message' => 'Skipped a block that is not a hand: "'.$this->snippet($block).'"'];

                continue;
            }

            // Honour the "include bomb / splash pots" preferences.
            if (! $this->options->includeBombPots && $this->isBombPot($block)) {
                $excludedBombPots++;

                continue;
            }
            if (! $this->options->includeSplashPots && $this->isSplashPot($block)) {
                $excludedSplashPots++;

                continue;
            }

            // A run-it-twice hand becomes one hand per board when enabled.
            $runs = $this->options->runItTwiceMode === 'split'
                ? $this->splitRunItTwice($block)
                : null;

            if ($runs !== null) {
                $warnings[] = ['hand' => $index + 1, 'message' => 'Run-it-twice hand split into '.count($runs).' hands (ids -1..-'.count($runs).'), one per board, each with its own pot. Verify the amounts against your tracker.'];
            }

            foreach ($runs ?? [$block] as $subBlock) {
                $index++;
                [$text, $summary, $handWarnings] = $this->convertHand($subBlock, $index);
                $outHands[] = $text;
                if ($summary !== null) {
                    $summaries[] = $summary;
                }
                foreach ($handWarnings as $w) {
                    $warnings[] = ['hand' => $index, 'message' => $w];
                }
            }
        }

        $output = $outHands === [] ? '' : implode("\n\n\n", $outHands)."\n\n\n";

        return new ConversionResult(
            $output, $index, $summaries, $warnings,
            $excludedBombPots, $excludedSplashPots,
        );
    }

    /** Ordinal words CoinPoker uses for run-it-twice street markers. */
    private const ORDINALS = ['FIRST' => 1, 'SECOND' => 2, 'THIRD' => 3, 'FOURTH' => 4];

    /**
     * Turn a run-it-twice hand into one raw (still CoinPoker-format) hand per
     * board, each carrying its share of the pot. Returns null when the hand is
     * not run-it-twice or its structure cannot be split confidently — the caller
     * then keeps it as a single hand.
     *
     * @return array<int, string>|null
     */
    private function splitRunItTwice(string $hand): ?array
    {
        if (! preg_match('/\*\*\*\s+(?:FIRST|SECOND|THIRD|FOURTH)\s+(?:FLOP|TURN|RIVER)\s+\*\*\*/', $hand)) {
            return null;
        }

        $lines = explode("\n", $hand);

        $firstMarker = null;
        $summary = null;
        $runs = 0;
        foreach ($lines as $i => $line) {
            if (preg_match('/^\*\*\*\s+(FIRST|SECOND|THIRD|FOURTH)\s+(?:FLOP|TURN|RIVER)\s+\*\*\*/', $line, $m)) {
                $firstMarker ??= $i;
                $runs = max($runs, self::ORDINALS[$m[1]]);
            } elseif ($summary === null && rtrim($line) === '*** SUMMARY ***') {
                $summary = $i;
            }
        }
        if ($firstMarker === null || $summary === null || $runs < 2) {
            return null;
        }

        // --- walk the body: street markers, shared betting, per-run showdowns ---
        $streetOrder = [];                         // FLOP, TURN, RIVER as they appear
        $boardMarker = [];                         // street => [run => "*** TURN *** [...] [x]"]
        $actionByStreet = [];                      // street => [shared betting lines]
        $showdownByRun = array_fill(1, $runs, []); // run => [shows + collected lines]
        $sharedShowdown = [];                      // single "*** SHOW DOWN ***" case
        $mode = 'street';
        $curStreet = null;
        $curRun = null;

        for ($i = $firstMarker; $i < $summary; $i++) {
            $line = rtrim($lines[$i]);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\*\*\*\s+(FIRST|SECOND|THIRD|FOURTH)\s+(FLOP|TURN|RIVER)\s+\*\*\*(.*)$/', $line, $m)) {
                $mode = 'street';
                $curStreet = $m[2];
                if (! in_array($curStreet, $streetOrder, true)) {
                    $streetOrder[] = $curStreet;
                }
                $boardMarker[$curStreet][self::ORDINALS[$m[1]]] = '*** '.$m[2].' ***'.rtrim($m[3]);

                continue;
            }
            if (preg_match('/^\*\*\*\s+(FIRST|SECOND|THIRD|FOURTH)\s+SHOW\s?DOWN\s+\*\*\*/', $line, $m)) {
                $mode = 'showdown';
                $curRun = self::ORDINALS[$m[1]];

                continue;
            }
            if (preg_match('/^\*\*\*\s+SHOW\s?DOWN\s+\*\*\*/', $line)) {
                $mode = 'showdown';
                $curRun = null;

                continue;
            }
            if (str_starts_with($line, '*** ')) {
                continue; // any other marker
            }

            if ($mode === 'showdown') {
                if ($curRun !== null) {
                    $showdownByRun[$curRun][] = $line;
                } else {
                    $sharedShowdown[] = $line;
                }
            } elseif ($curStreet !== null) {
                $actionByStreet[$curStreet][] = $line;
            }
        }

        // --- summary: pot, rake, per-run boards, seat lines ---
        $totalPot = $totalRake = 0.0;
        $boardsByRun = [];
        $plainBoards = [];
        $seatLines = [];
        for ($i = $summary + 1; $i < count($lines); $i++) {
            $line = rtrim($lines[$i]);
            if (preg_match('/^Total pot\s+\D*?([\d,.]+)(?:.*?\|\s*Rake\s+\D*?([\d,.]+))?/iu', $line, $m)) {
                $totalPot = $this->money($m[1]);
                $totalRake = isset($m[2]) && $m[2] !== '' ? $this->money($m[2]) : 0.0;
            } elseif (preg_match('/^(?:(FIRST|SECOND|THIRD|FOURTH)\s+)?Board\s+\[(.*)\]\s*$/', $line, $m)) {
                $cards = trim($m[2]);
                if ($cards === '') {
                    continue;
                }
                if ($m[1] !== '') {
                    $boardsByRun[self::ORDINALS[$m[1]]] = $cards;
                } else {
                    $plainBoards[] = $cards;
                }
            } elseif (preg_match('/^Seat\s+\d+:/', $line)) {
                $seatLines[] = $line;
            }
        }

        if ($boardsByRun === []) {
            foreach ($plainBoards as $k => $b) {
                $boardsByRun[$k + 1] = $b;
            }
        }
        for ($r = 1; $r <= $runs; $r++) {
            if (! isset($boardsByRun[$r])) {
                return null; // structure we do not recognise
            }
        }

        // --- collected amounts per run ---
        $runCollected = array_fill(1, $runs, []); // run => [name => amount]
        foreach ($showdownByRun as $run => $sdLines) {
            foreach ($sdLines as $l) {
                if (preg_match('/^(\S+)\s+collected\s+\D*?([\d,.]+)\s+from\s+/iu', $l, $m)) {
                    $runCollected[$run][$m[1]] = ($runCollected[$run][$m[1]] ?? 0) + $this->money($m[2]);
                }
            }
        }
        $sharedShows = [];
        if ($runCollected === array_fill(1, $runs, []) && $sharedShowdown !== []) {
            // Single "*** SHOW DOWN ***": distribute collected lines across runs,
            // keep the shows/mucks in every run.
            $k = 0;
            foreach ($sharedShowdown as $l) {
                if (preg_match('/^(\S+)\s+collected\s+\D*?([\d,.]+)\s+from\s+/iu', $l, $m)) {
                    $run = ($k % $runs) + 1;
                    $runCollected[$run][$m[1]] = ($runCollected[$run][$m[1]] ?? 0) + $this->money($m[2]);
                    $k++;
                } else {
                    $sharedShows[] = $l;
                }
            }
        }

        $rakePerRun = $this->splitMoney($totalRake, $runs);
        $potFallback = $this->splitMoney($totalPot, $runs);

        $prefix = array_slice($lines, 0, $firstMarker);
        $prefix[0] = preg_replace('/(Hand\s+#)(\S+?)(:)/', '${1}${2}-%RUN%$3', $prefix[0], 1) ?? $prefix[0];

        $out = [];
        for ($run = 1; $run <= $runs; $run++) {
            $block = array_map(fn ($l) => str_replace('%RUN%', (string) $run, rtrim($l)), $prefix);

            foreach ($streetOrder as $street) {
                if (! isset($boardMarker[$street][$run])) {
                    continue;
                }
                $block[] = $boardMarker[$street][$run];
                foreach ($actionByStreet[$street] ?? [] as $l) {
                    $block[] = $l;
                }
            }

            $runShows = $showdownByRun[$run] ?: $sharedShows;
            if ($runShows !== [] || array_sum($runCollected[$run] ?: []) > 0) {
                $block[] = '*** SHOWDOWN ***';
                foreach ($runShows as $l) {
                    if (! preg_match('/\bcollected\s+\D*?[\d,.]+\s+from\s+/iu', $l)) {
                        $block[] = $l;
                    }
                }
                foreach ($runCollected[$run] as $name => $amount) {
                    $block[] = $name.' collected ₮'.$this->fmtMoney((float) $amount).' from pot';
                }
            }

            $collectedSum = array_sum($runCollected[$run] ?: []);
            $potK = $collectedSum > 0 ? $collectedSum + $rakePerRun[$run] : $potFallback[$run];

            $block[] = '*** SUMMARY ***';
            $block[] = 'Total pot ₮'.$this->fmtMoney($potK).' | Rake ₮'.$this->fmtMoney($rakePerRun[$run]);
            $block[] = 'Board ['.$boardsByRun[$run].']';

            foreach ($seatLines as $seatLine) {
                $block[] = $this->rebuildRitSeatLine($seatLine, $run, $runCollected, $showdownByRun, $sharedShows);
            }

            $out[] = implode("\n", $block);
        }

        return $out;
    }

    /**
     * Rebuild a summary seat line for one run of a run-it-twice hand. CoinPoker
     * merges both runs onto one line ("... and lost with X, and won (₮..) with Y");
     * this derives a clean per-run line from that run's showdown data.
     *
     * @param  array<int, array<string, float>>  $runCollected
     * @param  array<int, array<int, string>>  $showdownByRun
     * @param  array<int, string>  $sharedShows
     */
    private function rebuildRitSeatLine(string $seatLine, int $run, array $runCollected, array $showdownByRun, array $sharedShows): string
    {
        if (! preg_match('/^(Seat\s+\d+):\s+(\S+)\s*(.*)$/', $seatLine, $m)) {
            return $seatLine;
        }
        [$seat, $name, $rest] = [$m[1], $m[2], $m[3]];

        $showLines = $showdownByRun[$run] ?: $sharedShows;
        $shown = null;
        foreach ($showLines as $l) {
            if (preg_match('/^'.preg_quote($name, '/').':\s+shows\s+\[([^\]]+)\]\s*(?:\(([^)]*)\))?/i', $l, $sm)) {
                $shown = ['cards' => trim($sm[1]), 'desc' => trim($sm[2] ?? '')];
                break;
            }
        }

        $amount = (float) ($runCollected[$run][$name] ?? 0);

        if ($shown !== null) {
            $tail = 'showed ['.$shown['cards'].'] and '
                .($amount > 0 ? 'won (₮'.$this->fmtMoney($amount).')' : 'lost')
                .($shown['desc'] !== '' ? ' with '.$shown['desc'] : '');

            return $seat.': '.$name.' '.$tail;
        }

        if (preg_match('/(folded\s+(?:on the|before)\s+\w+(?:\s+\w+)?)/i', $rest, $fm)) {
            return $seat.': '.$name.' '.$fm[1];
        }
        if ($amount > 0) {
            return $seat.': '.$name.' collected (₮'.$this->fmtMoney($amount).')';
        }

        return $seat.": {$name} mucked";
    }

    private function money(string $raw): float
    {
        return (float) str_replace([',', ' '], '', $raw);
    }

    private function fmtMoney(float $v): string
    {
        return $v === floor($v) ? (string) (int) $v : number_format($v, 2, '.', '');
    }

    /**
     * Split an amount into $n parts of 2 decimals; the remainder goes to run 1.
     *
     * @return array<int, float>
     */
    private function splitMoney(float $total, int $n): array
    {
        $each = floor(($total / $n) * 100) / 100;
        $parts = array_fill(1, $n, $each);
        $parts[1] = round($total - $each * ($n - 1), 2);

        return $parts;
    }

    /**
     * @return array{0: string, 1: HandSummary|null, 2: array<int, string>}
     */
    private function convertHand(string $hand, int $index): array
    {
        $warnings = [];
        $lines = explode("\n", $hand);

        $isTournament = str_contains($lines[0], 'Tournament #');
        $sym = $this->options->currencySymbol;

        // A run-it-twice hand still carrying its CoinPoker "FIRST/SECOND" street
        // markers: PokerTracker imports those natively, so keep them verbatim
        // (only the split mode, handled earlier, rewrites them).
        $isRit = (bool) preg_match(
            '/^\*\*\*\s+(?:FIRST|SECOND|THIRD|FOURTH)\s+(?:FLOP|TURN|RIVER|SHOW\s?DOWN)\s+\*\*\*/mi',
            $hand,
        );

        // --- table / blinds / button context (for summary position tags) ---
        $buttonSeat = null;
        $seatByName = [];
        $sbName = $bbName = null;

        foreach ($lines as $line) {
            if (preg_match('/^Table\s+\'.*\'\s+.*Seat\s+#(\d+)\s+is the button/', $line, $m)) {
                $buttonSeat = (int) $m[1];
            } elseif (preg_match('/^Seat\s+(\d+):\s+(.+?)\s+\(/', $line, $m)) {
                $seatByName[$m[2]] = (int) $m[1];
            } elseif (preg_match('/^(.+?):\s+posts small blind/', $line, $m)) {
                $sbName = $m[1];
            } elseif (preg_match('/^(.+?):\s+posts big blind/', $line, $m)) {
                $bbName = $m[1];
            }
        }

        $positions = []; // seat number => "button" | "small blind" | "big blind"
        if ($sbName !== null && isset($seatByName[$sbName])) {
            $positions[$seatByName[$sbName]] = 'small blind';
        }
        if ($bbName !== null && isset($seatByName[$bbName])) {
            $positions[$seatByName[$bbName]] = 'big blind';
        }
        if ($buttonSeat !== null) {
            $positions[$buttonSeat] = 'button'; // button wins ties (e.g. heads-up)
        }

        // --- header ---
        $lines[0] = $isTournament
            ? $this->convertTournamentHeader($lines[0], $warnings)
            : $this->convertCashHeader($lines[0], $warnings);

        // --- body, line by line ---
        $out = [$lines[0]];
        $inShowdown = false;
        $inSummary = false;
        $showdownHasShow = false;
        $showdownBuffer = [];

        // Per-street betting state, used to expand CoinPoker's bare "ALLIN"
        // keyword into a real bets / calls / raises "... and is all-in" action.
        $streetIn = [];   // player name => chips committed on the current street
        $curBet = 0.0;    // highest street commitment so far (the amount to call)
        $cashedOut = false;

        $flush = function () use (&$out, &$showdownBuffer, &$showdownHasShow) {
            if ($showdownBuffer === []) {
                return;
            }
            if ($showdownHasShow) {
                $out[] = '*** SHOW DOWN ***';
            }
            foreach ($showdownBuffer as $b) {
                $out[] = $b;
            }
            $showdownBuffer = [];
            $showdownHasShow = false;
        };

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if ($this->options->normalizeTetherSign) {
                $line = str_replace('₮', $this->options->currencySymbol, $line);
            }
            if (! $isTournament) {
                $line = $this->addCurrencySymbols($line);
            }
            if ($this->options->heroName !== 'Hero' && $this->options->heroName !== '') {
                $line = preg_replace('/\bHero\b/', $this->options->heroName, $line);
            }

            // ---- betting-state tracking + CoinPoker-only action keywords ----

            // A new betting round resets the per-street commitments.
            if (preg_match('/^\*\*\*\s+(?:(?:FIRST|SECOND|THIRD|FOURTH)\s+)?(?:FLOP|TURN|RIVER)\s+\*\*\*/i', $line)
                || preg_match('/^\*\*\*\s+(?:(?:FIRST|SECOND|THIRD|FOURTH)\s+)?SHOW\s?DOWN\s+\*\*\*/i', $line)) {
                $streetIn = [];
                $curBet = 0.0;
            }

            // "posts auto big blind" / "posts auto small blind" -> plain post.
            $line = preg_replace('/(:\s+posts )auto (big blind|small blind)\b/i', '$1$2', $line) ?? $line;

            // Blind / ante / straddle posts feed the betting state.
            if (preg_match('/^(.+?):\s+posts (?:small blind|big blind|the straddle|straddle)\s+\D*?([\d.]+)/i', $line, $m)) {
                $streetIn[$m[1]] = round(($streetIn[$m[1]] ?? 0) + (float) $m[2], 2);
                $curBet = max($curBet, $streetIn[$m[1]]);
            }

            // "<player>: STRADDLE ₮0.04" — a voluntary third blind. Model it as a
            // raise to that amount so the pot math and later "to" amounts line up.
            if (preg_match('/^(.+?):\s+STRADDLE\s+\D*?([\d.]+)\s*$/i', $line, $m)) {
                $name = $m[1];
                $to = (float) $m[2];
                $delta = round($to - $curBet, 2);
                $line = ($curBet > 0 && $delta > 0)
                    ? sprintf('%s: raises %s%s to %s%s', $name, $sym, $this->fmtMoney($delta), $sym, $this->fmtMoney($to))
                    : sprintf('%s: posts big blind %s%s', $name, $sym, $this->fmtMoney($to));
                $streetIn[$name] = $to;
                $curBet = max($curBet, $to);
                $out[] = $line;

                continue;
            }

            // "<player>: ALLIN ₮X" — CoinPoker's keyword for shoving the rest of a
            // stack. Expand to the real action (call / bet / raise) + " and is
            // all-in", which is what PokerTracker / HM3 parse.
            if (preg_match('/^(.+?):\s+ALLIN\s+\D*?([\d.]+)\s*$/i', $line, $m)) {
                $name = $m[1];
                $add = (float) $m[2];
                $prev = (float) ($streetIn[$name] ?? 0);
                $total = round($prev + $add, 2);
                $money = fn (float $v) => ($isTournament ? '' : $sym).$this->fmtMoney($v);

                if ($total <= $curBet + 0.0001) {
                    $line = sprintf('%s: calls %s and is all-in', $name, $money($add));
                } elseif ($curBet <= 0.0001) {
                    $line = sprintf('%s: bets %s and is all-in', $name, $money($add));
                } else {
                    $line = sprintf('%s: raises %s to %s and is all-in', $name, $money(round($total - $curBet, 2)), $money($total));
                }
                $streetIn[$name] = $total;
                $curBet = max($curBet, $total);
                $out[] = $line;

                continue;
            }

            // All-in insurance: "<player> cashed out the hand for ₮X | Cash Out
            // Fee ₮Y". Trackers have no concept of it — drop the line; the
            // "collected" lines already reflect the reduced distribution.
            if (preg_match('/^.+?\s+cashed out the hand\b/i', $line)) {
                $cashedOut = true;

                continue;
            }

            // Keep the betting state fresh from ordinary action lines.
            if (preg_match('/^(.+?):\s+(?:bets|calls)\s+\D*?([\d.]+)/i', $line, $m)) {
                $streetIn[$m[1]] = round(($streetIn[$m[1]] ?? 0) + (float) $m[2], 2);
                $curBet = max($curBet, $streetIn[$m[1]]);
            } elseif (preg_match('/^(.+?):\s+raises\s+\D*?[\d.]+\s+to\s+\D*?([\d.]+)/i', $line, $m)) {
                $streetIn[$m[1]] = (float) $m[2];
                $curBet = max($curBet, (float) $m[2]);
            }

            // Drop the per-player "Dealt to <name>" lines with no cards.
            if (preg_match('/^Dealt to \S.*$/', $line) && ! preg_match('/^Dealt to .+ \[.+\]\s*$/', $line)) {
                continue;
            }

            // "<player>: RETURN <amt>" -> "Uncalled bet ($amt) returned to <player>"
            if (preg_match('/^(.+?):\s+RETURN\s+(.+?)\s*$/', $line, $m)) {
                $amt = $isTournament ? trim($m[2]) : $this->ensureSymbol(trim($m[2]));
                $line = 'Uncalled bet ('.$amt.') returned to '.$m[1];
            }

            // Run-it-twice showdown markers: one per board. PokerTracker imports
            // CoinPoker run-it-twice natively, so keep the "*** FIRST SHOWDOWN ***"
            // spelling exactly as written.
            if (preg_match('/^\*\*\*\s+(FIRST|SECOND|THIRD|FOURTH)\s+SHOW\s?DOWN\s+\*\*\*\s*$/i', $line, $m)) {
                $out[] = '*** '.strtoupper($m[1]).' SHOWDOWN ***';

                continue;
            }

            // Showdown handling (buffer until we know whether anyone showed).
            if (rtrim($line) === '*** SHOWDOWN ***' || rtrim($line) === '*** SHOW DOWN ***') {
                $inShowdown = true;
                $showdownBuffer = [];
                $showdownHasShow = false;

                continue;
            }
            if ($inShowdown) {
                if (str_starts_with(ltrim($line), '*** ')) {
                    $flush();
                    $inShowdown = false;
                    // fall through to normal handling of this marker line
                } else {
                    if (preg_match('/:\s+shows \[|:\s+mucks|:\s+shows and/', $line)) {
                        $showdownHasShow = true;
                    }
                    $showdownBuffer[] = $line;

                    continue;
                }
            }

            // "Hand was run once" is noise. The run-twice/thrice line is kept
            // verbatim ("two times" / "with two boards") — PokerTracker's native
            // CoinPoker profile reads it and splits the pot across the boards.
            if (preg_match('/^Hand was run once\s*$/i', $line)) {
                continue;
            }
            if (preg_match('/^Hand was run (?!once\b).+$/i', $line)) {
                $warnings[] = 'Run-it-twice hand kept as one hand with its native CoinPoker markers — the tracker splits the pot itself on import.';
                $out[] = rtrim($line);

                continue;
            }
            // "SPLASH dropped ₮0.04" / "MEGA SPLASH dropped ₮0.20" — CoinPoker's
            // splash-pot marker (dropped, counted for stats).
            if (preg_match('/\bSPLASH\s+dropped\b/i', $line)) {
                continue;
            }
            // Board line: trim "[ .. ]" padding, drop it entirely when empty. For a
            // run-it-twice hand keep the "FIRST" / "SECOND" prefix so the tracker
            // can tell the boards apart; otherwise drop any stray ordinal.
            if (preg_match('/^((?:FIRST|SECOND|THIRD|FOURTH)\s+)?Board\s+\[(.*)\]\s*$/i', $line, $m)) {
                $inner = trim($m[2]);
                if ($inner === '') {
                    continue;
                }
                $prefix = ($isRit && $m[1] !== '') ? strtoupper(trim($m[1])).' ' : '';
                $line = $prefix.'Board ['.$inner.']';
            }
            if (preg_match('/^Game (started|ended):/', $line)) {
                continue;
            }
            if (rtrim($line) === '*** SUMMARY ***') {
                $inSummary = true;
            }

            // Seat summary line: add position tag, fix "won" -> "collected".
            if ($inSummary && preg_match('/^Seat\s+(\d+):\s+(.+)$/', $line, $m)) {
                $line = 'Seat '.$m[1].': '.$this->rewriteSeatSummary((int) $m[1], $m[2], $positions);
            }

            $out[] = $line;
        }
        $flush();

        $this->collapseShowdowns($out);
        $this->reconcilePot($out, $cashedOut, $warnings);
        $this->syncSeatSummaryAmounts($out, $isRit);

        $text = implode("\n", $out);
        $summary = $this->summarise($out, $isTournament);
        if ($summary !== null) {
            $summary->splashPot = $this->isSplashPot($hand);
            $summary->bombPot = $this->isBombPot($hand);
        }

        return [$text, $summary, $warnings];
    }

    /**
     * CoinPoker prints one "shows / collected" pair per sub-pot, so a player who
     * takes a main pot and a side pot appears two (or more) times. PokerTracker
     * only counts the first "collected" line per player, which then disagrees
     * with the total pot. Collapse each showdown section to one line per distinct
     * "shows" and one "collected ... from pot" per player carrying the summed
     * amount.
     *
     * @param  array<int, string>  $out
     */
    private function collapseShowdowns(array &$out): void
    {
        $isMarker = fn (string $l): bool => (bool) preg_match(
            '/^\*\*\*\s+(?:(?:FIRST|SECOND|THIRD|FOURTH)\s+)?SHOW\s?DOWN\s+\*\*\*/i',
            $l,
        );

        $result = [];
        $count = count($out);
        for ($i = 0; $i < $count; $i++) {
            $result[] = $out[$i];
            if (! $isMarker($out[$i])) {
                continue;
            }

            // Consume this showdown section up to the next "*** ..." marker.
            $section = [];
            $seen = [];
            $collectAt = [];   // player => index in $section of its collected line
            $collectSum = [];  // player => summed amount
            $collectSym = [];  // player => currency prefix as written

            for ($i++; $i < $count && ! str_starts_with(ltrim($out[$i]), '*** '); $i++) {
                $l = $out[$i];

                if (preg_match('/^(\S.*?)\s+collected\s+(\D*?)([\d.]+)\s+from\s+pot\b/i', $l, $m)) {
                    $player = $m[1];
                    if (isset($collectAt[$player])) {
                        $collectSum[$player] += (float) $m[3];

                        continue;
                    }
                    $collectAt[$player] = count($section);
                    $collectSum[$player] = (float) $m[3];
                    $collectSym[$player] = $m[2];
                    $section[] = $l;

                    continue;
                }

                $key = trim($l);
                if (isset($seen[$key])) {
                    continue; // repeated "X: shows [..]" from the next sub-pot
                }
                $seen[$key] = true;
                $section[] = $l;
            }
            $i--; // step back onto the marker line for the outer loop

            foreach ($collectAt as $player => $idx) {
                $section[$idx] = $player.' collected '
                    .$collectSym[$player].$this->fmtMoney(round($collectSum[$player], 2))
                    .' from pot';
            }

            foreach ($section as $l) {
                $result[] = $l;
            }
        }

        $out = $result;
    }

    /**
     * PokerTracker computes the pot from the betting action and checks it against
     * the sum of the "collected" lines plus rake — it ignores the "Total pot"
     * summary line. CoinPoker sometimes credits the winner less than the pot
     * actually holds (a splash-pot drop or an all-in cash-out skims money the
     * betting still shows), which trips that check. Recompute the pot from the
     * action, top up the largest "collected" line to close any shortfall, and
     * restate the summary line to match.
     *
     * @param  array<int, string>  $out
     * @param  array<int, string>  $warnings
     */
    private function reconcilePot(array &$out, bool $cashedOut, array &$warnings): void
    {
        // Split run-it-twice sub-hands ("#<id>-1:", "#<id>-2:") deliberately carry
        // the whole betting action but only their board's share of the pot —
        // splitRunItTwice() has already sized those, so leave them alone.
        if (isset($out[0]) && preg_match('/Hand\s+#[0-9]+-[0-9]+:/', $out[0])) {
            return;
        }

        $collectedIdx = [];
        foreach ($out as $k => $l) {
            if (preg_match('/^(\S.*?)\s+collected\s+\D*?([\d.]+)\s+from\s+pot\b/i', $l, $m)) {
                $collectedIdx[$k] = (float) $m[2];
            }
        }
        if ($collectedIdx === []) {
            return;
        }

        $potKey = null;
        $stated = $rake = 0.0;
        foreach ($out as $k => $l) {
            if (preg_match('/^Total pot\s+(\D*?)([\d.]+)(?:\s*\|\s*Rake\s+\D*?([\d.]+))?/i', $l, $m)) {
                $potKey = $k;
                $stated = (float) $m[2];
                $rake = isset($m[3]) && $m[3] !== '' ? (float) $m[3] : 0.0;
                break;
            }
        }
        if ($potKey === null) {
            return;
        }

        $actionPot = $this->potFromAction($out);
        $collectedSum = round(array_sum($collectedIdx), 2);
        $target = round($actionPot - $rake, 2);          // chips that must be handed out
        $shortfall = round($target - $collectedSum, 2);

        if ($shortfall >= 0.01) {
            // Credit the missing chips to the biggest winner.
            $biggest = array_keys($collectedIdx, max($collectedIdx), true)[0];
            $fixed = round($collectedIdx[$biggest] + $shortfall, 2);
            preg_match('/^(\S.*?)\s+collected\s+(\D*?)[\d.]+\s+from\s+pot\b(.*)$/i', $out[$biggest], $mm);
            $out[$biggest] = $mm[1].' collected '.$mm[2].$this->fmtMoney($fixed).' from pot'.($mm[3] ?? '');
            $collectedSum = round($collectedSum + $shortfall, 2);
            $warnings[] = $cashedOut
                ? 'Topped up the winning "collected" amount by '.$this->fmtMoney($shortfall).' (an all-in cash-out skimmed the pot; the tracker cannot model insurance, so the hand imports with the full pot going to the winner).'
                : 'Topped up the winning "collected" amount by '.$this->fmtMoney($shortfall).' so it matches the pot the betting built (CoinPoker under-reported the amount won).';
        }

        $realPot = round(max($collectedSum + $rake, $actionPot), 2);
        if (abs($realPot - $stated) >= 0.005) {
            $out[$potKey] = preg_replace('/^(Total pot\s+\D*?)[\d.]+/i', '${1}'.$this->fmtMoney($realPot), $out[$potKey], 1) ?? $out[$potKey];
        }
    }

    /**
     * PokerTracker also cross-checks the pot against the "Seat N: <player> ...
     * won ($x) / collected ($x)" amounts in the summary. CoinPoker writes only
     * one sub-pot there when a player won several, so after collapsing the
     * showdown and topping up the "collected" lines, mirror each winner's real
     * total onto their summary seat line. Run-it-twice lines are left alone —
     * their per-board amounts are already right and the tracker splits them.
     *
     * @param  array<int, string>  $out
     */
    private function syncSeatSummaryAmounts(array &$out, bool $isRit): void
    {
        if ($isRit) {
            return;
        }

        $won = [];
        foreach ($out as $l) {
            if (preg_match('/^(\S.*?)\s+collected\s+\D*?([\d.]+)\s+from\s+pot\b/i', $l, $m)) {
                $won[$m[1]] = round(($won[$m[1]] ?? 0) + (float) $m[2], 2);
            }
        }
        if ($won === []) {
            return;
        }

        $inSummary = false;
        foreach ($out as $k => $l) {
            if (rtrim($l) === '*** SUMMARY ***') {
                $inSummary = true;

                continue;
            }
            if (! $inSummary || ! preg_match('/^Seat\s+\d+:\s+(\S+)\s/', $l, $m)) {
                continue;
            }
            $player = $m[1];
            if (! isset($won[$player]) || str_contains($l, ', and ')) {
                continue; // not a winner here, or a merged run-it-twice line
            }
            if (preg_match_all('/\b(?:won|collected)\s+\(\D*?[\d.]+\)/i', $l) !== 1) {
                continue;
            }
            $out[$k] = preg_replace_callback(
                '/(\b(?:won|collected)\s+\()(\D*?)[\d.]+(\))/i',
                fn (array $mm): string => $mm[1].$mm[2].$this->fmtMoney($won[$player]).$mm[3],
                $l,
                1,
            ) ?? $l;
        }
    }

    /**
     * Total chips wagered, reconstructed from the converted action lines: blinds,
     * antes and straddles, every bet / call / raise (by its "to" delta), less any
     * uncalled bet returned. Street commitments reset on each board street.
     *
     * @param  array<int, string>  $out
     */
    private function potFromAction(array $out): float
    {
        $pot = 0.0;
        $street = [];

        foreach ($out as $l) {
            if (preg_match('/^\*\*\*\s+(?:(?:FIRST|SECOND|THIRD|FOURTH)\s+)?(?:FLOP|TURN|RIVER|SHOW\s?DOWN)\s+\*\*\*/i', $l)) {
                $street = [];

                continue;
            }
            if (preg_match('/^(.+?):\s+posts\s+(?:small blind|big blind|the ante|ante|straddle|the straddle|button blind|missed blind|dead)\s+\D*?([\d.]+)/i', $l, $m)) {
                $pot += (float) $m[2];
                $street[$m[1]] = round(($street[$m[1]] ?? 0) + (float) $m[2], 2);

                continue;
            }
            if (preg_match('/^(.+?):\s+(?:bets|calls)\s+\D*?([\d.]+)/i', $l, $m)) {
                $pot += (float) $m[2];
                $street[$m[1]] = round(($street[$m[1]] ?? 0) + (float) $m[2], 2);

                continue;
            }
            if (preg_match('/^(.+?):\s+raises\s+\D*?[\d.]+\s+to\s+\D*?([\d.]+)/i', $l, $m)) {
                $to = (float) $m[2];
                $pot += round($to - ($street[$m[1]] ?? 0), 2);
                $street[$m[1]] = $to;

                continue;
            }
            if (preg_match('/^Uncalled bet\s+\(\D*?([\d.]+)\)\s+returned to\s+(.+?)\s*$/i', $l, $m)) {
                $pot -= (float) $m[1];
                $street[$m[2]] = round(($street[$m[2]] ?? 0) - (float) $m[1], 2);
            }
        }

        return round($pot, 2);
    }

    private function isSplashPot(string $hand): bool
    {
        // Real CoinPoker markers: "SPLASH dropped ₮…" or "MEGA SPLASH dropped ₮…".
        return (bool) preg_match('/\bSPLASH\s+dropped\b/i', $hand)
            || (bool) preg_match('/\bsplash[ _-]?pot\b/i', $hand);
    }

    private function isBombPot(string $hand): bool
    {
        // CoinPoker tags the game code in the header, e.g. "NLH BombPot (...)".
        if (preg_match('/\bBomb\s?Pot\b/i', $hand)) {
            return true;
        }

        // Structural fallback: no blinds posted, everyone antes, straight to a flop.
        return ! preg_match('/:\s+posts (?:small|big) blind/i', $hand)
            && (bool) preg_match('/:\s+posts [^\n]*ante/i', $hand)
            && (bool) preg_match('/\*\*\*\s+(?:FIRST\s+)?FLOP\s+\*\*\*/', $hand);
    }

    private function convertCashHeader(string $header, array &$warnings): string
    {
        $pattern = '/^(?:CoinPoker|PokerStars)\s+Hand\s+#(\S+?):\s+(.+?)\s+\(([^)]*)\)\s*(?:-\s*)?'
            .'(\d{4}\/\d{2}\/\d{2}\s+\d{1,2}:\d{2}:\d{2})\s*([A-Za-z]{2,5})?(.*)$/';

        if (! preg_match($pattern, $header, $m)) {
            $warnings[] = 'Could not fully parse the hand header; left it mostly as-is.';

            return preg_replace('/^CoinPoker(\s+Hand\s+#)/i', $this->options->roomName.'$1', $header, 1) ?? $header;
        }

        [$full, $id, $game, $stakes, $date, $tz, $rest] = array_pad($m, 7, '');

        // Drop an already-present "[.... ET]" bracket so re-runs don't stack them.
        $rest = preg_replace('/^\s*\[[^\]]*\]\s*/', '', $rest) ?? $rest;

        $game = $this->mapGame($game, $warnings);
        $stakes = $this->normaliseStakes($stakes);
        $when = $this->renderTimezone($date, $tz ?: null, $warnings);

        return rtrim(sprintf('%s Hand #%s:  %s (%s) - %s %s',
            $this->options->roomName, $id, $game, $stakes, $when, ltrim($rest)
        ));
    }

    private function convertTournamentHeader(string $header, array &$warnings): string
    {
        // "CoinPoker Hand #x: Tournament #y, <buyin> NLH - Level III (a/b) - <date> CEST"
        $header = preg_replace('/^CoinPoker(\s+Hand\s+#)/i', $this->options->roomName.'$1', $header, 1) ?? $header;

        $header = preg_replace_callback('/,\s*(Freeroll|[^ ]+)\s+([A-Z0-9]{2,6})\s+-\s+Level/', function (array $m) use (&$warnings): string {
            $buyIn = $m[1];
            if (stripos($buyIn, 'freeroll') === false) {
                $buyIn = preg_replace_callback('/\d+(?:\.\d+)?/', fn ($n) => $this->options->currencySymbol.$n[0], $buyIn);
                if (! str_contains($buyIn, $this->options->currencyCode)) {
                    $buyIn .= ' '.$this->options->currencyCode;
                }
            }

            return ', '.$buyIn.' '.$this->mapGame($m[2], $warnings).' - Level';
        }, $header, 1);

        // Timezone on the trailing date.
        $header = preg_replace_callback(
            '/(\d{4}\/\d{2}\/\d{2}\s+\d{1,2}:\d{2}:\d{2})\s*([A-Za-z]{2,5})?/',
            fn (array $m) => $this->renderTimezone($m[1], $m[2] ?? null, $warnings),
            $header,
            1
        );

        return str_replace('₮', $this->options->currencySymbol, $header);
    }

    private function mapGame(string $code, array &$warnings): string
    {
        $code = trim($code);
        // CoinPoker appends a variant tag to the game code for special pots,
        // e.g. "NLH BombPot". The game itself is unchanged, so drop the tag.
        $code = trim((string) preg_replace('/\s+Bomb\s?Pot$/i', '', $code));

        $key = strtoupper($code);
        if (isset(self::GAMES[$key])) {
            return self::GAMES[$key];
        }
        if (stripos($code, 'limit') !== false) {
            return $code; // already a full game name
        }
        $warnings[] = 'Unknown game code "'.$code.'" left unchanged — check it imports.';

        return $code;
    }

    private function normaliseStakes(string $stakes): string
    {
        $sym = $this->options->currencySymbol;
        $stakes = str_replace('₮', $sym, trim($stakes));
        // Drop any existing currency word, then re-add ours.
        $stakes = preg_replace('/\s+(USDT|USD|EUR|GBP|CHIPS)\b/i', '', $stakes) ?? $stakes;
        // If CoinPoker omitted a symbol entirely, prefix every numeric token.
        if (! str_contains($stakes, $sym)) {
            $stakes = preg_replace('/\d+(?:\.\d+)?/', $sym.'$0', $stakes) ?? $stakes;
        }

        // Bomb-pot headers carry a third value (the ante), e.g. "$0.01/$0.02/$0.04".
        // The trackers' limit parser only accepts small blind / big blind; the ante
        // is still stated on the "posts ante" lines, so keep just the first two.
        if (preg_match('#^(\S*?[\d.]+\s*/\s*\S*?[\d.]+)\s*/\s*\S*?[\d.]+#', $stakes, $m)) {
            $stakes = $m[1];
        }

        return trim($stakes).' '.$this->options->currencyCode;
    }

    private function renderTimezone(string $stamp, ?string $tz, array &$warnings): string
    {
        $tzUpper = $tz ? strtoupper($tz) : null;

        if ($this->options->timezoneMode === 'keep') {
            return $tz ? "$stamp $tz" : $stamp;
        }

        $offset = $tzUpper !== null && array_key_exists($tzUpper, self::ET_OFFSETS)
            ? self::ET_OFFSETS[$tzUpper]
            : null;

        if ($offset === null) {
            $offset = $this->options->fallbackEtOffsetHours;
            if ($tzUpper !== null) {
                $warnings[] = 'Unknown timezone "'.$tz.'" — used a '.$offset.'h offset to Eastern time.';
            }
        }

        try {
            $dt = new DateTimeImmutable(str_replace('/', '-', $stamp), new DateTimeZone('UTC'));
            $et = $dt->modify(sprintf('%+d hours', -$offset));
        } catch (\Throwable) {
            $warnings[] = 'Could not parse the timestamp; left the time unchanged.';

            return $tz ? "$stamp $tz" : $stamp;
        }

        $etStamp = $et->format('Y/m/d').' '.((int) $et->format('G')).':'.$et->format('i:s');

        if ($this->options->timezoneMode === 'et') {
            return $etStamp.' '.$this->options->etLabel;
        }

        // 'dual' (default)
        $stdLabel = $tzUpper !== null ? (self::STD_LABELS[$tzUpper] ?? $tz) : $this->options->etLabel;

        return sprintf('%s %s [%s %s]', $stamp, $stdLabel, $etStamp, $this->options->etLabel);
    }

    /**
     * @param  array<int, string>  $positions
     */
    private function rewriteSeatSummary(int $seat, string $rest, array $positions): string
    {
        // CoinPoker: "didn't show" is not a summary phrase the trackers parse.
        $rest = preg_replace('/\bdidn\'t show\b/i', 'mucked', $rest) ?? $rest;

        // All-in insurance: "... and cashed out for ₮X | Cash Out Fee ₮Y" is not
        // parseable. Treat the player as having lost the pot (the insurance
        // payout is a side transaction the tracker cannot represent).
        $rest = preg_replace('/\s+and cashed out for\b.*$/i', ' and lost', $rest) ?? $rest;
        $rest = preg_replace('/\s+cashed out for\b.*$/i', ' mucked', $rest) ?? $rest;

        // Already converted (has a position tag) — leave it alone.
        if (preg_match('/^\S.*\s\((?:button|small blind|big blind)\)\s/', $rest)) {
            return $rest;
        }

        // rest looks like "NAME won (₮0.05)" or "NAME folded before Flop (didn't bet)"
        if (! preg_match('/^(.+?)\s+(folded|won|collected|showed|mucked|lost|sitting|is )/', $rest, $m)) {
            // fall back: just split on the first space group
            [$name, $tail] = array_pad(preg_split('/\s+/', $rest, 2) ?: [$rest, ''], 2, '');
        } else {
            $name = $m[1];
            $tail = ltrim(substr($rest, strlen($name)));
        }

        // "won (X)" -> "collected (X)" for a non-showdown win. A showdown line
        // ("showed [..] and won (X) with ...") keeps "won"; a run-it-twice
        // no-showdown line ("won (X), and won (Y)") gets every one replaced.
        $tail = str_contains($tail, 'showed')
            ? preg_replace('/^won \(/', 'collected (', $tail)
            : preg_replace('/\bwon \(/', 'collected (', $tail);
        $tail ??= '';

        $pos = $positions[$seat] ?? null;
        if ($pos === 'small blind' || $pos === 'big blind') {
            // Blind posters did put money in — the format omits "(didn't bet)".
            $tail = preg_replace('/\s*\(didn\'t bet\)\s*$/', '', $tail) ?? $tail;
        }

        return $pos !== null ? "$name ($pos) $tail" : "$name $tail";
    }

    /**
     * Safety net: prefix a currency symbol to bare decimal amounts in CASH hands
     * (only fires when CoinPoker omitted the tether sign). Never double-prefixes.
     */
    private function addCurrencySymbols(string $line): string
    {
        $sym = preg_quote($this->options->currencySymbol, '/');
        $num = '(?<![\d'.$sym.'£€$])(\d+(?:\.\d+)?)';
        $s = $this->options->currencySymbol;

        $rules = [
            ['/\(\s*'.$num.'(\s+in chips\))/', '('.$s.'\1\2'],
            ['/(posts (?:small blind|big blind|the ante|ante|straddle|button blind|missed blind)\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(:\s+(?:bets|calls)\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(:\s+raises\s+)'.$num.'(\s+to\s+)'.$num.'/', '\1'.$s.'\2\3'.$s.'\4'],
            ['/(Uncalled bet\s+\()'.$num.'(\))/', '\1'.$s.'\2\3'],
            ['/(\s+collected\s+)'.$num.'(\s+from\s+)/', '\1'.$s.'\2\3'],
            ['/(Total pot\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(\|\s+Rake\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(\|\s+JP Rake\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(Main pot\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(Side pot(?:-\d+)?\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/((?:collected|won|lost)\s+\()'.$num.'(\))/', '\1'.$s.'\2\3'],
        ];

        foreach ($rules as [$pattern, $replacement]) {
            $line = preg_replace($pattern, $replacement, $line);
        }

        return $line;
    }

    private function ensureSymbol(string $amount): string
    {
        $sym = $this->options->currencySymbol;

        return str_starts_with($amount, $sym) ? $amount : $sym.ltrim($amount, '$€£');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function summarise(array $lines, bool $isTournament): ?HandSummary
    {
        $header = $lines[0] ?? '';

        if (! preg_match('/Hand\s+#(\S+?):/', $header, $idm)) {
            return null;
        }

        $game = 'Unknown';
        if (preg_match('/:\s+(?:Tournament\s+#\S+,.*?\s)?([A-Za-z0-9\' \/]+?)\s+\(/', $header, $gm)) {
            $game = trim($gm[1]);
        }

        $stakes = '';
        if (preg_match('/\(([^)]*?\/[^)]*?)\)/', $header, $sm)) {
            $stakes = trim(preg_replace('/\s+[A-Z]{3}$/', '', $sm[1]) ?? $sm[1]);
        }

        $playedAt = null;
        if (preg_match('/(\d{4}\/\d{2}\/\d{2}\s+\d{1,2}:\d{2}:\d{2})/', $header, $dm)) {
            $playedAt = $dm[1];
        }

        $table = '';
        $maxSeats = null;
        if (isset($lines[1]) && preg_match('/^Table\s+\'(.+)\'\s+(?:(\d+)-max)?/', $lines[1], $tm)) {
            $table = $tm[1];
            $maxSeats = isset($tm[2]) && $tm[2] !== '' ? (int) $tm[2] : null;
        }

        $hero = null;
        foreach ($lines as $line) {
            if (preg_match('/^Dealt to (.+?) \[/', $line, $hm)) {
                $hero = $hm[1];
                break;
            }
        }

        return new HandSummary(
            handId: rtrim($idm[1], ':'),
            format: $isTournament ? 'Tournament' : 'Cash',
            game: $game,
            stakes: $stakes,
            table: $table,
            maxSeats: $maxSeats,
            playedAt: $playedAt,
            hero: $hero,
        );
    }

    private function snippet(string $text): string
    {
        $first = strtok($text, "\n") ?: $text;

        return mb_strlen($first) > 80 ? mb_substr($first, 0, 77).'...' : $first;
    }
}
