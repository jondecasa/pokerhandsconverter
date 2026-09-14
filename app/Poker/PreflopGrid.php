<?php

namespace App\Poker;

/**
 * The standard 13x13 preflop starting-hand matrix: pairs on the diagonal,
 * suited combos above it, offsuit combos below it.
 */
class PreflopGrid
{
    public const RANKS = ['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'];

    /** @return array<int, array<int, string>> 13 rows of 13 hand codes, e.g. "AKs", "72o", "TT". */
    public static function rows(): array
    {
        return array_map(
            fn (int $row) => array_map(fn (int $col) => self::hand($row, $col), range(0, 12)),
            range(0, 12)
        );
    }

    /** @return array<int, string> All 169 hand codes, in row-major order. */
    public static function hands(): array
    {
        return array_merge(...self::rows());
    }

    private static function hand(int $row, int $col): string
    {
        $ranks = self::RANKS;

        if ($row === $col) {
            return $ranks[$row].$ranks[$row];
        }

        return $row < $col
            ? $ranks[$row].$ranks[$col].'s'
            : $ranks[$col].$ranks[$row].'o';
    }
}
