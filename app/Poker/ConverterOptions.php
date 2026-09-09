<?php

namespace App\Poker;

/**
 * Tunable settings for the CoinPoker -> PokerTracker 4 conversion.
 *
 * Defaults reproduce the hand-history layout that PokerTracker 4 / Hold'em
 * Manager 3 import, including the European dual-timezone stamp
 * ("... 12:01:21 CET [2026/09/09 6:01:21 ET]").
 */
class ConverterOptions
{
    public function __construct(
        /**
         * Room name written into the "<Room> Hand #..." header line. Kept as
         * "CoinPoker" so PokerTracker 4 / Hold'em Manager 3 import the file with
         * their native CoinPoker profile. Only override this if your tracker
         * needs a different site name on the header.
         */
        public string $roomName = 'CoinPoker',

        /** Currency symbol that replaces CoinPoker's tether sign (₮) and any bare amounts. */
        public string $currencySymbol = '$',

        /** Currency code appended inside the stakes / buy-in parenthesis. */
        public string $currencyCode = 'USD',

        /**
         * Timezone rendering:
         *  - 'dual': "<time> <STD label> [<ET time> ET]"  (European dual-stamp format)
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

        /**
         * Run-it-twice handling:
         *  - 'keep' (default): one hand, run-it-twice markers normalised to the
         *    format PT4 / HM3 import natively (they split the pot themselves)
         *  - 'split': emit one hand per board (ids <id>-1, <id>-2, ...), each
         *    with its own pot — note the tracker's own pot check may not agree
         */
        public string $runItTwiceMode = 'keep',

        /**
         * Screen name to substitute for CoinPoker's anonymised "Hero". Leave as
         * "Hero" to make no change.
         */
        public string $heroName = 'Hero',

        /** Keep bomb-pot hands in the output. */
        public bool $includeBombPots = true,

        /** Keep splash-pot hands in the output. */
        public bool $includeSplashPots = true,
    ) {}

    public static function fromConfig(array $overrides = []): self
    {
        $c = config('pokercoinverter.converter', []);

        return new self(
            roomName: $overrides['room_name'] ?? $c['room_name'] ?? 'CoinPoker',
            currencySymbol: $overrides['currency_symbol'] ?? $c['currency_symbol'] ?? '$',
            currencyCode: $overrides['currency_code'] ?? $c['currency_code'] ?? 'USD',
            timezoneMode: $overrides['timezone_mode'] ?? $c['timezone_mode'] ?? 'dual',
            etLabel: $overrides['et_label'] ?? $c['et_label'] ?? 'ET',
            fallbackEtOffsetHours: (int) ($overrides['fallback_et_offset_hours'] ?? $c['fallback_et_offset_hours'] ?? 6),
            normalizeTetherSign: (bool) ($overrides['normalize_tether_sign'] ?? $c['normalize_tether_sign'] ?? true),
            runItTwiceMode: $overrides['run_it_twice_mode'] ?? $c['run_it_twice_mode'] ?? 'keep',
            heroName: $overrides['hero_name'] ?? $c['hero_name'] ?? 'Hero',
            includeBombPots: (bool) ($overrides['include_bomb_pots'] ?? true),
            includeSplashPots: (bool) ($overrides['include_splash_pots'] ?? true),
        );
    }
}
