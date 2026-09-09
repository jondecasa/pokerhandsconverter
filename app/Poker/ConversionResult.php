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
}
