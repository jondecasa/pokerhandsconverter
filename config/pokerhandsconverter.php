<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription packages
    |--------------------------------------------------------------------------
    |
    | Packages ("plans") now live in the `plans` table and are managed by admins
    | at /admin/plans. PlanSeeder creates the two starter packages below on a
    | fresh install; after that, edit them in the admin UI.
    |
    */

    // Cashier subscription "type" (a.k.a. name). Keep it stable once live.
    'subscription_name' => 'default',

    // Where the public contact form delivers, and the address shown in the
    // legal pages.
    'contact_email' => env('CONTACT_EMAIL', 'info@pokerhandsconverter.com'),

    // Default free-trial length (days) when a package does not set its own. 0 = none.
    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 7),

    // Stake ladder offered in the admin "stake covered" dropdown.
    'stakes' => [
        'NL2', 'NL5', 'NL10', 'NL16', 'NL25', 'NL50', 'NL100',
        'NL200', 'NL500', 'NL1000', 'NL2000', 'NL5000', 'NL10000',
    ],

    /*
    |--------------------------------------------------------------------------
    | Converter defaults
    |--------------------------------------------------------------------------
    |
    | These feed App\Poker\ConverterOptions. Tune them against your own real
    | CoinPoker exports if the output does not import cleanly into your tracker.
    |
    */

    'converter' => [
        // Site name kept on the "<Room> Hand #..." header so PokerTracker 4 /
        // Hold'em Manager 3 import with their native CoinPoker profile.
        'room_name' => env('CONVERTER_ROOM_NAME', 'CoinPoker'),
        'currency_symbol' => env('CONVERTER_CURRENCY_SYMBOL', '$'),
        'currency_code' => env('CONVERTER_CURRENCY_CODE', 'USD'),

        // dual = "<time> CET [<time> ET]" (European dual-stamp format)
        // et   = "<time> ET" only
        // keep = leave CoinPoker's time and label untouched
        'timezone_mode' => env('CONVERTER_TIMEZONE_MODE', 'dual'),
        'et_label' => env('CONVERTER_ET_LABEL', 'ET'),

        // Hours to subtract from the source time for timezones not in the
        // built-in table (Central Europe -> US Eastern is 6).
        'fallback_et_offset_hours' => (int) env('CONVERTER_FALLBACK_ET_OFFSET_HOURS', 6),

        'normalize_tether_sign' => (bool) env('CONVERTER_NORMALIZE_TETHER', true),

        // keep = one hand, run-it-twice markers normalised for native tracker
        // import; split = one hand per board
        'run_it_twice_mode' => env('CONVERTER_RUN_IT_TWICE_MODE', 'keep'),
    ],

    // Hard limit for uploaded files (kilobytes).
    'max_upload_kb' => (int) env('CONVERTER_MAX_UPLOAD_KB', 20480),

    /*
    |--------------------------------------------------------------------------
    | Languages of the public site
    |--------------------------------------------------------------------------
    |
    | The default language lives at the site root; every other one under its
    | URL prefix (e.g. /zh-hant/pricing). Text is translated through
    | lang/<locale>.json, with the English string as the key, so a missing
    | translation just shows English. Only the pages in `localized_pages` (plus
    | blog posts that have a translation) exist in the other languages; the
    | authenticated app stays in English.
    |
    */

    'default_locale' => 'en',

    'locales' => [
        'en' => ['native' => 'English', 'prefix' => '', 'hreflang' => 'en', 'html' => 'en', 'og' => 'en_US', 'carbon' => 'en'],
        'zh-Hant' => ['native' => '繁體中文', 'prefix' => 'zh-hant', 'hreflang' => 'zh-Hant', 'html' => 'zh-Hant', 'og' => 'zh_TW', 'carbon' => 'zh_TW'],
    ],

    'localized_pages' => ['home', 'pricing', 'contact', 'blog.index'],

    /*
    |--------------------------------------------------------------------------
    | Blog publishing API
    |--------------------------------------------------------------------------
    |
    | /api/posts lets a trusted client (the `blog:push` artisan command run from
    | a dev machine) create and update posts on this site. With no token set the
    | API is switched off entirely. `url` is only used by the client side, to
    | know which site to push to.
    |
    */

    'blog_api' => [
        'token' => env('BLOG_API_TOKEN'),
        'url' => env('BLOG_API_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow
    |--------------------------------------------------------------------------
    |
    | Tells Bing, Yandex, Naver, Seznam and Yep about new/changed posts right
    | away. The key (8-128 letters, digits or dashes) is public by design: it is
    | served at /<key>.txt so the engines can verify the site. Only active when
    | APP_ENV=production and a key is set.
    |
    */

    'indexnow' => [
        'key' => env('INDEXNOW_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Baidu
    |--------------------------------------------------------------------------
    |
    | Baidu's webmaster platform (ziyuan.baidu.com) needs the site to be
    | verified and gives an API token for pushing new URLs. `verification` is
    | the content of the <meta name="baidu-site-verification"> tag it shows
    | you; `site` is the site exactly as registered there (defaults to the
    | home URL). Only the non-default-language URLs are pushed, and only in
    | production with a token set.
    |
    */

    'baidu' => [
        'verification' => env('BAIDU_SITE_VERIFICATION'),
        'token' => env('BAIDU_PUSH_TOKEN'),
        'site' => env('BAIDU_PUSH_SITE'),
    ],
];
