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
    ) {}

    public function toArray(): array
    {
        return [
            'hand_id' => $this->handId,
            'format' => $this->format,
            'game' => $this->game,
            'stakes' => $this->stakes,
            'table' => $this->table,
            'max_seats' => $this->maxSeats,
            'played_at' => $this->playedAt,
            'hero' => $this->hero,
        ];
    }
}
