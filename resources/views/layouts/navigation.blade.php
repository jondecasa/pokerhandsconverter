@php
    $nav = [
        ['route' => 'dashboard',          'active' => request()->routeIs('dashboard'),                                        'label' => 'Dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
        ['route' => 'convert.create',     'active' => request()->routeIs('convert.*') || request()->routeIs('conversions.*'), 'label' => 'Convert',   'icon' => 'M4 4v6h6M20 20v-6h-6M4 10a8 8 0 0114-5.3M20 14a8 8 0 01-14 5.3'],
        ['route' => 'subscription.plans', 'active' => request()->routeIs('subscription.plans'),                               'label' => 'Plans',     'icon' => 'M3 7h18M3 12h18M3 17h18'],
    ];

    $initials = collect(explode(' ', trim(Auth::user()->name)))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') ?: 'U';
@endphp

<div class="flex h-full flex-col bg-slate-900">
    {{-- Brand --}}
    <a href="{{ route('dashboard') }}" class="flex h-16 items-center gap-2.5 border-b border-white/10 px-5">
        <img src="{{ asset('logo.svg') }}" alt="" width="36" height="36" class="h-9 w-9 rounded-[10px]">
        <span class="text-lg font-extrabold tracking-tight text-white">Poker<span class="text-indigo-400">Coinverter</span></span>
    </a>

    {{-- Primary nav --}}
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}"
               @class(['pc-navlink', 'pc-navlink-active' => $item['active']])
               @if ($item['active']) aria-current="page" @endif>
                <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach

        @if (auth()->user()?->isAdmin())
            <div class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Admin</div>
            <a href="{{ route('admin.plans.index') }}"
               @class(['pc-navlink', 'pc-navlink-active' => request()->routeIs('admin.*')])>
                <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Packages
            </a>
        @endif
    </nav>

    {{-- Account --}}
    <div class="border-t border-white/10 p-3" x-data="{ menu: false }" @click.outside="menu = false">
        <button type="button" @click="menu = !menu"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition hover:bg-white/5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-semibold text-indigo-300">{{ $initials }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-white">{{ Auth::user()->name }}</span>
                <span class="block truncate text-xs text-slate-400">{{ Auth::user()->email }}</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="menu && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="menu" x-cloak x-transition.origin.bottom class="mt-1 space-y-1">
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
                <svg class="h-5 w-5 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile &amp; CoinPoker ID
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
                    <svg class="h-5 w-5 opacity-80" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7M13 16v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
