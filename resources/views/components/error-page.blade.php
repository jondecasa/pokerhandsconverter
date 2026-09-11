@props(['code', 'title', 'message'])

<x-marketing-layout :title="$code.' — '.$title.' — PokerHandsConverter'" :description="$message">
    <section class="mx-auto flex min-h-[60vh] max-w-2xl flex-col items-center justify-center px-6 py-20 text-center">
        <div class="text-sm font-semibold tracking-wide text-indigo-600">Error {{ $code }}</div>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $title }}</h1>
        <p class="mt-4 text-slate-600">{{ $message }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('home') }}"
               class="rounded-xl bg-indigo-600 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                Back to home
            </a>
            <a href="{{ route('contact') }}"
               class="rounded-xl border border-slate-300 px-6 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Contact support
            </a>
        </div>
    </section>
</x-marketing-layout>
