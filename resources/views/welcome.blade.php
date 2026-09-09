<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PokerCoinverter — CoinPoker → PokerStars hand history converter</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex flex-col">
        <header class="w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
            <div class="text-lg font-extrabold tracking-tight">Poker<span class="text-indigo-600">Coinverter</span></div>
            <nav class="space-x-4 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}" class="font-medium hover:text-indigo-600">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="font-medium hover:text-indigo-600">Log in</a>
                    <a href="{{ route('register') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">Get started</a>
                @endauth
            </nav>
        </header>

        <main class="flex-1">
            <section class="max-w-6xl mx-auto px-6 pt-16 pb-20 text-center">
                <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
                    Turn CoinPoker hand histories into<br class="hidden sm:block">
                    <span class="text-indigo-600">PokerStars format</span> your tracker can read
                </h1>
                <p class="mt-6 text-lg text-gray-600 max-w-2xl mx-auto">
                    Upload a CoinPoker <code>.txt</code>, download a PokerStars-formatted <code>.txt</code>.
                    Import cleanly into Hold'em Manager 3, PokerTracker 4 and other HUDs. Cash games and tournaments.
                </p>
                <div class="mt-8 flex items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="inline-flex px-6 py-3 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-500">Start free trial</a>
                    <a href="{{ auth()->check() ? route('pricing') : route('login') }}" class="inline-flex px-6 py-3 border border-gray-300 rounded-md hover:bg-gray-100">See pricing</a>
                </div>
            </section>

            <section class="max-w-5xl mx-auto px-6 pb-20 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="text-2xl">1&#65039;&#8419;</div>
                    <h3 class="mt-2 font-semibold">Upload</h3>
                    <p class="text-sm text-gray-600 mt-1">Drop in the hand-history file CoinPoker exported.</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="text-2xl">2&#65039;&#8419;</div>
                    <h3 class="mt-2 font-semibold">Convert</h3>
                    <p class="text-sm text-gray-600 mt-1">We rewrite the header, currency and timezone to PokerStars format.</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <div class="text-2xl">3&#65039;&#8419;</div>
                    <h3 class="mt-2 font-semibold">Import</h3>
                    <p class="text-sm text-gray-600 mt-1">Load the result into your tracker and see your stats.</p>
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} PokerCoinverter. Not affiliated with CoinPoker or PokerStars.
        </footer>
    </div>
</body>
</html>
