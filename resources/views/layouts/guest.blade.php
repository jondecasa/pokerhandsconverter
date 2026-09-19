<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $appName = config('app.name', 'PokerHandsConverter');
            $pageTitle = trim(strip_tags((string) ($title ?? ($heading ?? ''))));
            $fullTitle = $pageTitle === '' ? $appName : (str_contains($pageTitle, $appName) ? $pageTitle : $pageTitle.' · '.$appName);
            $metaDescription = trim(strip_tags((string) ($description ?? ''))) ?: 'Log in or create your account to convert CoinPoker hand histories into a format PokerTracker 4 imports cleanly.';
        @endphp
        <title>{{ $fullTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4f46e5">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-700 antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 px-4 py-10">

            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight text-slate-900">
                <img src="{{ asset('logo.svg') }}" alt="PokerHandsConverter" width="40" height="40" class="h-10 w-10 rounded-[11px]">
                PokerHands<span class="text-indigo-600">Converter</span>
            </a>

            @isset($heading)
                <h1 class="mt-6 text-lg font-semibold text-slate-900">{{ $heading }}</h1>
            @endisset
            @isset($subheading)
                <p class="mt-1 text-sm text-slate-500">{{ $subheading }}</p>
            @endisset

            <div class="mt-6 w-full sm:max-w-md rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                {{ $slot }}
            </div>

            <a href="{{ route('home') }}" class="mt-6 text-xs text-slate-400 hover:text-slate-600">&larr; Back to home</a>
        </div>
    </body>
</html>
