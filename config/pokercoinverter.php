<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription plans
    |--------------------------------------------------------------------------
    |
    | Each plan maps to a Stripe Price ID. Create the products/prices in your
    | Stripe dashboard (test mode first) and drop the price IDs into your .env.
    | "amount" / "interval" / "blurb" are only used for rendering the pricing
    | page — Stripe remains the source of truth for what is actually charged.
    |
    */

    'plans' => [
        'monthly' => [
            'name' => 'Monthly',
            'price_id' => env('STRIPE_PRICE_MONTHLY'),
            'amount' => env('PLAN_MONTHLY_AMOUNT', '9'),
            'currency' => env('PLAN_CURRENCY', 'USD'),
            'interval' => 'month',
            'blurb' => 'Unlimited conversions, billed monthly. Cancel anytime.',
        ],
        'yearly' => [
            'name' => 'Yearly',
            'price_id' => env('STRIPE_PRICE_YEARLY'),
            'amount' => env('PLAN_YEARLY_AMOUNT', '90'),
            'currency' => env('PLAN_CURRENCY', 'USD'),
            'interval' => 'year',
            'blurb' => 'Two months free versus monthly. Unlimited conversions.',
        ],
    ],

    // Cashier subscription "type" (a.k.a. name). Keep it stable once live.
    'subscription_name' => 'default',

    // Free trial length in days applied at Stripe Checkout. 0 = no trial.
    'trial_days' => (int) env('PLAN_TRIAL_DAYS', 7),

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
        'room_name' => env('CONVERTER_ROOM_NAME', 'PokerStars'),
        'currency_symbol' => env('CONVERTER_CURRENCY_SYMBOL', '$'),
        'currency_code' => env('CONVERTER_CURRENCY_CODE', 'USD'),
        'timezone_mode' => env('CONVERTER_TIMEZONE_MODE', 'relabel'), // relabel | keep | convert
        'timezone_label' => env('CONVERTER_TIMEZONE_LABEL', 'ET'),
        'offset_hours' => (int) env('CONVERTER_OFFSET_HOURS', -5),
        'normalize_tether_sign' => (bool) env('CONVERTER_NORMALIZE_TETHER', true),
    ],

    // Hard limit for uploaded files (kilobytes).
    'max_upload_kb' => (int) env('CONVERTER_MAX_UPLOAD_KB', 20480),
];
