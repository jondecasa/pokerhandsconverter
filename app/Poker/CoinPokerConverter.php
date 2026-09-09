<?php

namespace App\Poker;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Converts a CoinPoker hand-history text file into a PokerStars-formatted one
 * so that trackers/HUDs (Hold'em Manager 3, PokerTracker 4, etc.) can import it.
 *
 * Strategy: a resilient, line-oriented transformation rather than a full
 * parse-and-rebuild. CoinPoker's export already mirrors the PokerStars layout,
 * so the conversion only needs to:
 *
 *   1. Rewrite the "CoinPoker Hand #..." header to "PokerStars Hand #...".
 *   2. Normalise the buy-in / stakes currency code (USDT -> USD) in the header.
 *   3. Relabel (or shift) the trailing timezone token on the date.
 *   4. For CASH games only, prefix a currency symbol to the bare decimal money
 *      amounts CoinPoker prints without one ("posts big blind 0.05" -> "$0.05",
 *      "(5 in chips)" -> "($5 in chips)", "Total pot 0.10" -> "$0.10", ...).
 *      Tournament chip amounts stay bare, exactly like real PokerStars.
 *   5. Replace the USDT tether sign (₮) with the configured currency symbol.
 *
 * Every non-obvious situation (run-it-twice boards, unknown blocks, missing
 * timezone, ...) is recorded as a warning instead of throwing.
 */
class CoinPokerConverter
{
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

        foreach ($blocks as $block) {
            $block = trim($block, "\n");
            if ($block === '') {
                continue;
            }

            if (! preg_match('/^(?:CoinPoker|PokerStars)\s+Hand\s+#/i', $block)) {
                $warnings[] = ['hand' => null, 'message' => 'Skipped a block that does not start with a hand header: "'.$this->snippet($block).'"'];

                continue;
            }

            $index++;
            [$text, $summary, $handWarnings] = $this->convertHand($block, $index);
            $outHands[] = $text;
            if ($summary !== null) {
                $summaries[] = $summary;
            }
            foreach ($handWarnings as $w) {
                $warnings[] = ['hand' => $index, 'message' => $w];
            }
        }

        // PokerStars files separate hands with one blank line.
        $output = $outHands === [] ? '' : implode("\n\n\n", $outHands)."\n\n\n";

        return new ConversionResult($output, $index, $summaries, $warnings);
    }

    /**
     * @return array{0: string, 1: HandSummary|null, 2: array<int, string>}
     */
    private function convertHand(string $hand, int $index): array
    {
        $warnings = [];
        $lines = explode("\n", $hand);

        $isTournament = str_contains($lines[0], 'Tournament #');

        $lines[0] = $this->convertHeader($lines[0], $isTournament, $warnings);

        foreach ($lines as $i => $line) {
            if ($i === 0) {
                continue;
            }

            if ($this->options->normalizeTetherSign) {
                $line = str_replace('₮', $this->options->currencySymbol, $line);
            }

            if (! $isTournament) {
                $line = $this->addCurrencySymbols($line);
            }

            if (str_contains($line, '*** FIRST FLOP ***') || str_contains($line, '*** SECOND ')) {
                $warnings[] = 'Run-it-twice board detected — most trackers only import the first board. Review hand #'.$index.'.';
            }

            $lines[$i] = $line;
        }

        $text = implode("\n", $lines);
        $summary = $this->summarise($lines, $isTournament);

        return [$text, $summary, $warnings];
    }

    private function convertHeader(string $header, bool $isTournament, array &$warnings): string
    {
        // 1. Room prefix.
        $header = preg_replace(
            '/^(?:CoinPoker|PokerStars)(\s+Hand\s+#)/i',
            $this->options->roomName.'$1',
            $header,
            1
        );

        // 2. Currency code inside the stakes/buy-in parenthesis or tournament buy-in.
        //    "($0.02/$0.05 USDT)" -> "($0.02/$0.05 USD)"
        $header = preg_replace(
            '/\b(USDT|USD|EUR|GBP|CHIPS)\b/',
            $this->options->currencyCode,
            $header
        );

        // Tournament buy-in "10+1" / "$10+$1" -> "$10+$1 USD"
        if ($isTournament) {
            $header = preg_replace_callback(
                '/(Tournament\s+#\S+,\s*)(Freeroll|\S+)/',
                function (array $m): string {
                    if (stripos($m[2], 'freeroll') !== false) {
                        return $m[1].'Freeroll';
                    }
                    $buyIn = $m[2];
                    // Ensure a symbol on each numeric component of "A+B(+C)".
                    $buyIn = preg_replace_callback('/\d+(?:\.\d+)?/', fn ($n) => $this->options->currencySymbol.$n[0], $buyIn);
                    if (! str_contains($buyIn, $this->options->currencyCode)) {
                        $buyIn .= ' '.$this->options->currencyCode;
                    }

                    return $m[1].$buyIn;
                },
                $header,
                1
            );
        }

        // 3. Timezone token on the trailing date.
        $header = preg_replace_callback(
            '/(\d{4}\/\d{2}\/\d{2}\s+\d{1,2}:\d{2}:\d{2})\s*([A-Za-z]{2,4})?/',
            function (array $m) use (&$warnings): string {
                $stamp = $m[1];
                $tz = $m[2] ?? null;

                if ($this->options->timezoneMode === 'keep') {
                    return $tz ? "$stamp $tz" : $stamp;
                }

                if ($this->options->timezoneMode === 'convert') {
                    try {
                        $dt = new DateTimeImmutable(str_replace('/', '-', $stamp), new DateTimeZone('UTC'));
                        $dt = $dt->modify(sprintf('%+d hours', $this->options->offsetHours));
                        $stamp = $dt->format('Y/m/d H:i:s');
                    } catch (\Throwable) {
                        $warnings[] = 'Could not parse the timestamp for timezone conversion; left the printed time unchanged.';
                    }
                }

                return $stamp.' '.$this->options->timezoneLabel;
            },
            $header,
            1
        );

        return $header;
    }

    /**
     * Prefix the configured currency symbol to the bare decimal amounts that
     * CoinPoker prints without one in CASH hands. Each rule only fires when the
     * amount is not already prefixed by a currency symbol.
     */
    private function addCurrencySymbols(string $line): string
    {
        $sym = preg_quote($this->options->currencySymbol, '/');
        // A number not already preceded by a currency-ish symbol.
        $num = '(?<![\d'.$sym.'£€$])(\d+(?:\.\d+)?)';
        $s = $this->options->currencySymbol;

        $rules = [
            // Seat stacks: "(1500 in chips)" -> "($1500 in chips)"
            ['/\(\s*'.$num.'(\s+in chips\))/', '($'.'\1\2'],

            // Blinds / antes / straddles
            ['/(posts small blind\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts big blind\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts the ante\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts ante\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts straddle\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts button blind\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(posts missed blind\s+)'.$num.'/', '\1'.$s.'\2'],

            // Actions: "Hero: bets 0.15", "Hero: calls 0.30"
            ['/(:\s+(?:bets|calls)\s+)'.$num.'/', '\1'.$s.'\2'],
            // "Hero: raises 0.30 to 0.75"
            ['/(:\s+raises\s+)'.$num.'(\s+to\s+)'.$num.'/', '\1'.$s.'\2\3'.$s.'\4'],

            // "Uncalled bet (0.40) returned to Hero"
            ['/(Uncalled bet\s+\()'.$num.'(\))/', '\1'.$s.'\2\3'],

            // "Hero collected 1.20 from pot" / "... from side pot"
            ['/(\s+collected\s+)'.$num.'(\s+from\s+(?:the\s+)?(?:main\s+|side\s+)?pot)/', '\1'.$s.'\2\3'],

            // Summary: "Total pot 0.30 | Rake 0.01"
            ['/(Total pot\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(\|\s+Rake\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(Main pot\s+)'.$num.'/', '\1'.$s.'\2'],
            ['/(Side pot(?:-\d+)?\s+)'.$num.'/', '\1'.$s.'\2'],

            // Summary per-seat: "collected (0.30)", "won (0.30)", "and lost (0.30)"
            ['/((?:collected|won|lost)\s+\()'.$num.'(\))/', '\1'.$s.'\2\3'],

            // "cashed out the hand for 12.34" (rare) and "bounty"
            ['/(cashed out the hand for\s+)'.$num.'/', '\1'.$s.'\2'],
        ];

        foreach ($rules as [$pattern, $replacement]) {
            $line = preg_replace($pattern, $replacement, $line);
        }

        return $line;
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
        $handId = rtrim($idm[1], ':');

        $game = 'Unknown';
        $stakes = '';
        $playedAt = null;

        if (preg_match('/:\s+(?:Tournament\s+#\S+,\s+.*?\s+)?(Hold\'em No Limit|Hold\'em Limit|Hold\'em Pot Limit|Omaha Pot Limit|Omaha Hi\/Lo Pot Limit|[A-Za-z0-9\'\/ ]+?)(?:\s+-\s+Level|\s+\()/', $header, $gm)) {
            $game = trim($gm[1]);
        }

        if ($isTournament) {
            if (preg_match('/\((\d+(?:\.\d+)?\/\d+(?:\.\d+)?)\)/', $header, $sm)) {
                $stakes = $sm[1];
            }
        } elseif (preg_match('/\(([^)]*?\/[^)]*?)\)/', $header, $sm)) {
            $stakes = trim($sm[1]);
        }

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
            handId: $handId,
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
