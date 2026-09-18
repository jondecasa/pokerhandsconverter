@use('App\Support\Locales')
<x-marketing-layout
    :title="__('Contact — PokerHandsConverter')"
    :description="__('Get in touch with the PokerHandsConverter team about conversions, billing or anything else.')">

    <section class="mx-auto max-w-3xl px-6 py-16">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('Contact us') }}</h1>
        <p class="mt-4 text-lg text-slate-600">
            {!! __('Questions about a conversion that didn’t import, billing, or anything else — send us a note. You can also email :email directly.', [
                'email' => '<a href="mailto:'.e(config('pokerhandsconverter.contact_email')).'" class="font-medium text-indigo-600 hover:text-indigo-500 break-words">'.e(config('pokerhandsconverter.contact_email')).'</a>',
            ]) !!}
        </p>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ Locales::route('contact.submit') }}" class="mt-8 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
            @csrf

            {{-- Honeypot — hidden from real users --}}
            <div class="hidden" aria-hidden="true">
                <label>Company <input type="text" name="company" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('Your name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-slate-700">{{ __('Subject') }} <span class="text-slate-400">({{ __('optional') }})</span></label>
                <input id="subject" name="subject" type="text" value="{{ old('subject') }}"
                       class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-700">{{ __('Message') }}</label>
                <textarea id="message" name="message" rows="6" required
                          class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                @error('message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ __('Send message') }}
            </button>
        </form>
    </section>
</x-marketing-layout>
