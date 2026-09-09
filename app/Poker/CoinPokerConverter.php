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

            // Drop the per-player "Dealt to <name>" lines with no cards.
            if (preg_match('/^Dealt to \S.*$/', $line) && ! preg_match('/^Dealt to .+ \[.+\]\s*$/', $line)) {
                continue;
            }

            // "<player>: RETURN <amt>" -> "Uncalled bet ($amt) returned to <player>"
            if (preg_match('/^(.+?):\s+RETURN\s+(.+?)\s*$/', $line, $m)) {
                $amt = $isTournament ? trim($m[2]) : $this->ensureSymbol(trim($m[2]));
                $line = 'Uncalled bet ('.$amt.') returned to '.$m[1];
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

            // Summary-only CoinPoker noise.
            if (preg_match('/^Hand was run (once|twice|\d+ times)\s*$/', $line)) {
                if (! str_contains($line, 'once')) {
                    // Only reached when splitting is off or the split could not
                    // be parsed — one hand with both boards is left in place.
                    $warnings[] = 'Run-it-twice hand left as a single hand with multiple boards — check how your tracker imports it.';
                }

                continue;
            }
            // "SPLASH dropped ₮0.04" / "MEGA SPLASH dropped ₮0.20" — CoinPoker's
            // splash-pot marker (dropped, counted for stats).
            if (preg_match('/\bSPLASH\s+dropped\b/i', $line)) {
                continue;
            }
            // Board line: drop it when empty, otherwise trim CoinPoker's
            // "[ Qh Ks Jh ]" padding to "[Qh Ks Jh]".
            if (preg_match('/^Board\s+\[(.*)\]\s*$/', $line, $m)) {
                $inner = trim($m[1]);
                if ($inner === '') {
                    continue;
                }
                $line = 'Board ['.$inner.']';
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

        $text = implode("\n", $out);
        $summary = $this->summarise($out, $isTournament);
        if ($summary !== null) {
            $summary->splashPot = $this->isSplashPot($hand);
            $summary->bombPot = $this->isBombPot($hand);
        }

        return [$text, $summary, $warnings];
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

        $tail = preg_replace('/^won \(/', 'collected (', $tail) ?? $tail;

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
