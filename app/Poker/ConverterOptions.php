<?php

namespace App\Poker;

/**
 * Tunable settings for the CoinPoker -> PokerStars conversion.
 *
 * The defaults match what Hold'em Manager 3 / PokerTracker 4 expect from a
 * PokerStars hand history. Adjust them from config/pokercoinverter.php or per
 * request if your real CoinPoker exports differ.
 */
class ConverterOptions
{
    public function __construct(
        /** Room name written into the "<Room> Hand #..." header line. */
        public string $roomName = 'PokerStars',

        /** Currency symbol prefixed to bare cash amounts ($, €, £...). */
        public string $currencySymbol = '$',

        /** Currency code written into the header, replacing USDT/USD/EUR... */
        public string $currencyCode = 'USD',

        /**
         * How to handle the trailing timezone token on the date.
         *  - 'relabel': keep the printed time, swap the label (UTC -> ET)
         *  - 'keep'   : leave the timezone token untouched
         *  - 'convert': shift the printed time by offsetHours and relabel
         */
        public string $timezoneMode = 'relabel',

        /** Target timezone label used by 'relabel' and 'convert'. */
        public string $timezoneLabel = 'ET',

        /** Hours to add to the source time when timezoneMode = 'convert'. */
        public int $offsetHours = -5,

        /** Replace the USDT tether sign (₮) with currencySymbol. */
        public bool $normalizeTetherSign = true,
    ) {}

    public static function fromConfig(array $overrides = []): self
    {
        $c = config('pokercoinverter.converter', []);

        return new self(
            roomName: $overrides['room_name'] ?? $c['room_name'] ?? 'PokerStars',
            currencySymbol: $overrides['currency_symbol'] ?? $c['currency_symbol'] ?? '$',
            currencyCode: $overrides['currency_code'] ?? $c['currency_code'] ?? 'USD',
            timezoneMode: $overrides['timezone_mode'] ?? $c['timezone_mode'] ?? 'relabel',
            timezoneLabel: $overrides['timezone_label'] ?? $c['timezone_label'] ?? 'ET',
            offsetHours: (int) ($overrides['offset_hours'] ?? $c['offset_hours'] ?? -5),
            normalizeTetherSign: (bool) ($overrides['normalize_tether_sign'] ?? $c['normalize_tether_sign'] ?? true),
        );
    }
}
