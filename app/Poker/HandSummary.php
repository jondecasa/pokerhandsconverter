<?php

namespace App\Poker;

/**
 * Lightweight, read-only description of a single hand, used for the preview
 * table and per-file statistics. It is never used to rebuild the hand text.
 */
class HandSummary
{
    public function __construct(
        public string $handId,
        public string $format,   // "Cash" | "Tournament"
        public string $game,     // e.g. "Hold'em No Limit"
        public string $stakes,   // e.g. "$0.02/$0.05" or "75/150"
        public string $table,    // table name
        public ?int $maxSeats,   // 2, 6, 9...
        public ?string $playedAt,// "2024/01/15 18:30:00"
        public ?string $hero,    // hero screen name, if detectable
        public bool $splashPot = false,
        public bool $bombPot = false,
    ) {}

    /** Big blind in currency units for a cash hand ("$0.02/$0.05" -> 0.05). */
    public function bigBlind(): ?float
    {
        if ($this->format !== 'Cash') {
            return null;
        }
        if (! preg_match('#/[^\d]*([\d.]+)#', $this->stakes, $m)) {
            return null;
        }

        return (float) $m[1] ?: null;
    }

    /** e.g. "NL5" for $0.02/$0.05 cash; null for tournaments. */
    public function stakeLevel(): ?string
    {
        $bb = $this->bigBlind();
        if ($bb === null) {
            return null;
        }
        $n = $bb * 100;

        return 'NL'.($n == floor($n) ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.'));
    }

    public function toArray(): array
    {
        return [
            'hand_id' => $this->handId,
            'format' => $this->format,
            'game' => $this->game,
            'stakes' => $this->stakes,
            'stake_level' => $this->stakeLevel(),
            'table' => $this->table,
            'max_seats' => $this->maxSeats,
            'played_at' => $this->playedAt,
            'hero' => $this->hero,
            'splash_pot' => $this->splashPot,
            'bomb_pot' => $this->bombPot,
        ];
    }
}
