@props([
    'title' => 'PokerHandsConverter — Convert CoinPoker hand histories for PokerTracker 4',
    'description' => 'PokerHandsConverter turns your CoinPoker hand-history files into a format PokerTracker 4 imports cleanly. Cash games and tournaments.',
    'canonical' => null,
])

@php($canonicalUrl = $canonical ?? url()->current())

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="PokerHandsConverter">
    <meta property="og:image" content="{{ asset('images/icons/icon-512.png') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ asset('images/icons/icon-512.png') }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'PokerHandsConverter',
            'url' => route('home'),
            'logo' => asset('images/icons/icon-512.png'),
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>
    @stack('schema')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-600 bg-white">

    {{-- ===================== NAV ===================== --}}
    <header
        x-data="{ open: false }"
        class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur"
    >
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-lg font-extrabold tracking-tight text-slate-900">
                <img src="{{ asset('logo.svg') }}" alt="PokerHandsConverter" width="36" height="36" class="h-9 w-9 rounded-[10px]">
                PokerHands<span class="text-indigo-600">Converter</span>
            </a>

            <div class="hidden items-center gap-8 md:flex">
                <a href="{{ route('home') }}#how" class="text-sm font-medium text-slate-600 hover:text-slate-900">How it works</a>
                <a href="{{ route('home') }}#features" class="text-sm font-medium text-slate-600 hover:text-slate-900">Features</a>
                <a href="{{ route('pricing') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Pricing</a>
                <a href="{{ route('home') }}#faq" class="text-sm font-medium text-slate-600 hover:text-slate-900">FAQ</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Contact</a>
            </div>

            <div class="hidden items-center gap-3 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Go to dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-slate-900">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Start free trial
                    </a>
                @endauth
            </div>

            <button @click="open = !open" class="md:hidden" aria-label="Toggle menu">
                <svg class="h-6 w-6 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </nav>

        <div x-show="open" x-cloak class="border-t border-slate-200 px-6 py-4 md:hidden">
            <div class="flex flex-col gap-3">
                <a href="{{ route('home') }}#how" class="text-sm font-medium text-slate-700">How it works</a>
                <a href="{{ route('home') }}#features" class="text-sm font-medium text-slate-700">Features</a>
                <a href="{{ route('pricing') }}" class="text-sm font-medium text-slate-700">Pricing</a>
                <a href="{{ route('home') }}#faq" class="text-sm font-medium text-slate-700">FAQ</a>
                <a href="{{ route('contact') }}" class="text-sm font-medium text-slate-700">Contact</a>
                <hr class="border-slate-200">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white">Go to dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white">Start free trial</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    {{-- ===================== FOOTER ===================== --}}
    <footer class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <div class="max-w-sm">
                    <div class="flex items-center gap-2 text-base font-extrabold text-slate-900">
                        PokerHands<span class="text-indigo-600">Converter</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">
                        Convert CoinPoker hand histories into a format PokerTracker 4 reads, so your tracker and HUD just work.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-8 text-sm sm:grid-cols-3">
                    <div>
                        <div class="font-semibold text-slate-900">Product</div>
                        <ul class="mt-3 space-y-2">
                            <li><a href="{{ route('home') }}#features" class="text-slate-500 hover:text-slate-900">Features</a></li>
                            <li><a href="{{ route('home') }}#how" class="text-slate-500 hover:text-slate-900">How it works</a></li>
                            <li><a href="{{ route('pricing') }}" class="text-slate-500 hover:text-slate-900">Pricing</a></li>
                        </ul>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900">Account</div>
                        <ul class="mt-3 space-y-2">
                            @auth
                                <li><a href="{{ route('dashboard') }}" class="text-slate-500 hover:text-slate-900">Dashboard</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="text-slate-500 hover:text-slate-900">Log in</a></li>
                                <li><a href="{{ route('register') }}" class="text-slate-500 hover:text-slate-900">Create account</a></li>
                            @endauth
                        </ul>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900">Legal</div>
                        <ul class="mt-3 space-y-2">
                            <li><a href="{{ route('terms') }}" class="text-slate-500 hover:text-slate-900">Terms</a></li>
                            <li><a href="{{ route('privacy') }}" class="text-slate-500 hover:text-slate-900">Privacy</a></li>
                            <li><a href="{{ route('contact') }}" class="text-slate-500 hover:text-slate-900">Contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="mt-10 border-t border-slate-200 pt-6 text-xs text-slate-400">
                &copy; {{ date('Y') }} PokerHandsConverter. Not affiliated with, endorsed by, or sponsored by CoinPoker,
                PokerTracker or Hold'em Manager.
            </div>
        </div>
    </footer>

    {{-- ===================== COOKIE NOTICE ===================== --}}
    <div
        x-data="{
            show: false,
            init() {
                try { this.show = ! localStorage.getItem('pc_cookie_ack') } catch (e) { this.show = true }
            },
            ack() {
                try { localStorage.setItem('pc_cookie_ack', '1') } catch (e) {}
                this.show = false
            }
        }"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="fixed inset-x-0 bottom-0 z-50 px-4 pb-4 sm:px-6"
    >
        <div class="mx-auto flex max-w-3xl flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">
                We use cookies to keep you signed in and to run the site. We don&rsquo;t use them for advertising or
                third-party tracking. See our <a href="{{ route('privacy') }}" class="font-medium text-indigo-600 hover:text-indigo-500">privacy&nbsp;policy</a>.
            </p>
            <button
                type="button"
                x-on:click="ack()"
                class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                Got it
            </button>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
