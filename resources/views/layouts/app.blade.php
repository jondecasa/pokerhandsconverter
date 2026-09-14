<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PokerHandsConverter') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4f46e5">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-700">
        <div x-data="{ sidebar: false }" class="min-h-screen bg-slate-50">

            {{-- ---------- Sidebar: desktop ---------- --}}
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 lg:block">
                @include('layouts.navigation')
            </aside>

            {{-- ---------- Sidebar: mobile drawer ---------- --}}
            <div x-show="sidebar" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
                <div x-show="sidebar" x-transition.opacity class="fixed inset-0 bg-slate-900/60" @click="sidebar = false"></div>
                <div x-show="sidebar" x-cloak
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                     class="fixed inset-y-0 left-0 w-64">
                    <button type="button" class="absolute -right-10 top-2 p-2 text-white" @click="sidebar = false" aria-label="Close menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    @include('layouts.navigation')
                </div>
            </div>

            {{-- ---------- Content ---------- --}}
            <div class="lg:pl-64">
                <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-slate-50/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="-ml-1 rounded-md p-2 text-slate-500 hover:bg-slate-200/60 lg:hidden"
                            @click="sidebar = true" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    @isset($header)
                        <div class="min-w-0 flex-1 [&_h2]:!text-lg [&_h2]:!font-semibold [&_h2]:!text-slate-900 [&_h2]:truncate">
                            {{ $header }}
                        </div>
                    @endisset

                    <a href="{{ route('convert.create') }}"
                       class="ml-auto hidden shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 sm:inline-flex">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        New conversion
                    </a>
                </header>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        <style>[x-cloak]{display:none!important}</style>
    </body>
</html>
