@php($trialDays = (int) config('pokercoinverter.trial_days'))
@php($plans = config('pokercoinverter.plans'))

<x-marketing-layout title="Pricing — PokerCoinverter">

    <section class="mx-auto max-w-5xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Pricing</h1>
            <p class="mt-4 text-lg text-slate-600">
                One subscription, unlimited conversions.
                @if ($trialDays > 0) Every plan starts with a {{ $trialDays }}-day free trial. @endif
            </p>
        </div>

        <div class="mx-auto mt-14 grid max-w-3xl gap-6 sm:grid-cols-2">
            @foreach ($plans as $key => $plan)
                <div class="flex flex-col rounded-2xl border bg-white p-8 {{ $key === 'yearly' ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-slate-200' }}">
                    @if ($key === 'yearly')
                        <span class="mb-3 inline-flex w-max rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Best value</span>
                    @endif
                    <div class="text-sm font-semibold text-slate-900">{{ $plan['name'] }}</div>
                    <div class="mt-2">
                        <span class="text-5xl font-extrabold text-slate-900">{{ ($plan['currency'] ?? 'USD') === 'USD' ? '$' : '' }}{{ $plan['amount'] }}</span>
                        <span class="text-slate-500">/ {{ $plan['interval'] }}</span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">{{ $plan['blurb'] }}</p>

                    <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-700">
                        @foreach ([
                            'Unlimited CoinPoker → PokerStars conversions',
                            'Cash games and tournaments',
                            'Per-upload timezone handling',
                            'Conversion history and re-downloads',
                            'Warnings for anything unusual in a hand',
                            'Cancel anytime from your dashboard',
                        ] as $feature)
                            <li class="flex gap-2">
                                <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <a href="{{ route('subscription.plans') }}" class="mt-8 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            Choose {{ $plan['name'] }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="mt-8 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            @if ($trialDays > 0) Start free trial @else Get started @endif
                        </a>
                        <p class="mt-2 text-center text-xs text-slate-400">Already have an account? <a href="{{ route('login') }}" class="underline">Log in</a></p>
                    @endauth
                </div>
            @endforeach
        </div>

        <div class="mx-auto mt-16 max-w-2xl">
            <h2 class="text-xl font-bold text-slate-900">Billing questions</h2>
            <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ([
                    ['How does the free trial work?', ($trialDays > 0 ? "You get $trialDays days of full access. You won't be charged until the trial ends, and you can cancel before then at no cost." : 'There is currently no free trial — your subscription starts immediately.')],
                    ['Which payment methods do you accept?', 'All major cards, processed securely by Stripe. PokerCoinverter never stores or sees your card details.'],
                    ['Can I switch between monthly and yearly?', 'Yes. Change your plan any time from the billing portal linked in your dashboard; Stripe prorates the difference.'],
                    ['What happens if I cancel?', 'You keep access until the end of the period you already paid for, then the account reverts to no active subscription. Your conversion history is preserved.'],
                    ['Do you offer refunds?', 'Contact us within 14 days of a charge if the converter did not work for your files and we could not fix it — see our Terms.'],
                ] as [$q, $a])
                    <details class="group py-5" x-data>
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-slate-900">
                            {{ $q }}
                            <svg class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-45" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        </summary>
                        <p class="mt-3 text-sm text-slate-600">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

</x-marketing-layout>
