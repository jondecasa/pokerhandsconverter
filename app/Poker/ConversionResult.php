<?php

namespace App\Poker;

class ConversionResult
{
    /**
     * @param  string  $output  Full converted hand-history text.
     * @param  int  $handCount  Number of hands successfully converted.
     * @param  array<int, HandSummary>  $hands  Per-hand summaries (for preview/stats).
     * @param  array<int, array{hand: int|null, message: string}>  $warnings
     */
    public function __construct(
        public string $output,
        public int $handCount,
        public array $hands = [],
        public array $warnings = [],
        public int $excludedBombPots = 0,
        public int $excludedSplashPots = 0,
    ) {}

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /** @return array<string, int> format => count */
    public function formatBreakdown(): array
    {
        $out = [];
        foreach ($this->hands as $h) {
            $out[$h->format] = ($out[$h->format] ?? 0) + 1;
        }

        return $out;
    }

    public function splashPotCount(): int
    {
        return count(array_filter($this->hands, fn (HandSummary $h) => $h->splashPot));
    }

    public function bombPotCount(): int
    {
        return count(array_filter($this->hands, fn (HandSummary $h) => $h->bombPot));
    }

    /** Highest cash big blind seen in the file (null if there are no cash hands). */
    public function maxCashBigBlind(): ?float
    {
        $bbs = array_filter(array_map(fn (HandSummary $h) => $h->bigBlind(), $this->hands));

        return $bbs ? max($bbs) : null;
    }

    /** Label for the highest cash stake seen, e.g. "NL50". */
    public function maxCashStakeLevel(): ?string
    {
        $best = null;
        $bestBb = -1.0;
        foreach ($this->hands as $h) {
            $bb = $h->bigBlind();
            if ($bb !== null && $bb > $bestBb) {
                $bestBb = $bb;
                $best = $h->stakeLevel();
            }
        }

        return $best;
    }
}
