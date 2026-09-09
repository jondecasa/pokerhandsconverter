<?php

namespace App\Poker;

/**
 * Tunable settings for the CoinPoker -> PokerStars conversion.
 *
 * Defaults reproduce the layout that Hold'em Manager 3 / PokerTracker 4 expect
 * from a PokerStars hand history, including the European dual-timezone stamp
 * ("... 12:01:21 CET [2026/09/09 6:01:21 ET]").
 */
class ConverterOptions
{
    public function __construct(
        /** Room name written into the "<Room> Hand #..." header line. */
        public string $roomName = 'PokerStars',

        /** Currency symbol that replaces CoinPoker's tether sign (₮) and any bare amounts. */
        public string $currencySymbol = '$',

        /** Currency code appended inside the stakes / buy-in parenthesis. */
        public string $currencyCode = 'USD',

        /**
         * Timezone rendering:
         *  - 'dual': "<time> <STD label> [<ET time> ET]"  (real PokerStars EU format)
         *  - 'et'  : "<ET time> ET"                        (US single-label format)
         *  - 'keep': leave the CoinPoker time and label untouched
         */
        public string $timezoneMode = 'dual',

        /** Label used for the Eastern-Time part. */
        public string $etLabel = 'ET',

        /**
         * Hours to subtract from the source time to get Eastern time when the
         * source timezone abbreviation is not in the built-in table.
         */
        public int $fallbackEtOffsetHours = 6,

        /** Replace CoinPoker's USDT tether sign (₮) with currencySymbol. */
        public bool $normalizeTetherSign = true,
    ) {}

    public static function fromConfig(array $overrides = []): self
    {
        $c = config('pokercoinverter.converter', []);

        return new self(
            roomName: $overrides['room_name'] ?? $c['room_name'] ?? 'PokerStars',
            currencySymbol: $overrides['currency_symbol'] ?? $c['currency_symbol'] ?? '$',
            currencyCode: $overrides['currency_code'] ?? $c['currency_code'] ?? 'USD',
            timezoneMode: $overrides['timezone_mode'] ?? $c['timezone_mode'] ?? 'dual',
            etLabel: $overrides['et_label'] ?? $c['et_label'] ?? 'ET',
            fallbackEtOffsetHours: (int) ($overrides['fallback_et_offset_hours'] ?? $c['fallback_et_offset_hours'] ?? 6),
            normalizeTetherSign: (bool) ($overrides['normalize_tether_sign'] ?? $c['normalize_tether_sign'] ?? true),
        );
    }
}
