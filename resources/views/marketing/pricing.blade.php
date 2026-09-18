@use('App\Support\Locales')
@php($trialDays = $plans->max(fn ($p) => $p->effectiveTrialDays()) ?? 0)
@php($billingFaqs = [
    [__('How does the free trial work?'), ($trialDays > 0 ? __('You get :days days of full access. You won\'t be charged until the trial ends, and you can cancel before then at no cost.', ['days' => $trialDays]) : __('There is currently no free trial — your subscription starts immediately.'))],
    [__('Which payment methods do you accept?'), __('All major cards, processed securely by Stripe. PokerHandsConverter never stores or sees your card details.')],
    [__('Can I switch between monthly and yearly?'), __('Yes. Change your plan any time from the billing portal linked in your dashboard; Stripe prorates the difference.')],
    [__('What happens if I cancel?'), __('You keep access until the end of the period you already paid for, then the account reverts to no active subscription. Your conversion history is preserved.')],
    [__('Do you offer refunds?'), __('Contact us within 14 days of a charge if the converter did not work for your files and we could not fix it — see our Terms.')],
])

<x-marketing-layout
    :title="__('Pricing — PokerHandsConverter')"
    :description="__('Unlimited CoinPoker → PokerTracker 4 conversions on every package. Simple monthly and yearly pricing, cancel anytime.')">

    @push('schema')
        {{ \App\Support\JsonLd::script([
            '@type' => 'FAQPage',
            'mainEntity' => collect($billingFaqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq[0],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq[1],
                ],
            ])->all(),
        ]) }}
    @endpush

    <section class="mx-auto max-w-5xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">{{ __('Pricing') }}</h1>
            <p class="mt-4 text-lg text-slate-600">
                {{ __('Unlimited conversions on every package.') }}
                @if ($trialDays > 0) {{ __('Free trial included.') }} @endif
            </p>
        </div>

        <div class="mx-auto mt-14 grid max-w-5xl gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($plans as $plan)
                <div class="flex flex-col rounded-2xl border bg-white p-8 {{ $plan->is_highlighted ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-slate-200' }}">
                    @if ($plan->is_highlighted)
                        <span class="mb-3 inline-flex w-max rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">{{ __('Best value') }}</span>
                    @endif
                    <div class="text-sm font-semibold text-slate-900">{{ __($plan->name) }}</div>
                    <div class="mt-2">
                        <span class="text-5xl font-extrabold text-slate-900">{{ $plan->priceLabel() }}</span>
                        @unless ($plan->isFree())<span class="text-slate-500">/ {{ __($plan->interval) }}</span>@endunless
                    </div>
                    @if ($plan->stakesText())
                        <div class="mt-2 inline-flex w-max rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $plan->stakesText() }}</div>
                    @endif
                    <p class="mt-3 text-sm text-slate-600">{{ __((string) $plan->description) }}</p>

                    <ul class="mt-6 flex-1 space-y-3 text-sm text-slate-700">
                        @forelse ($plan->featureList() as $feature)
                            <li class="flex gap-2">
                                <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __($feature) }}
                            </li>
                        @empty
                            <li class="flex gap-2">
                                <svg viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Unlimited CoinPoker → PokerTracker 4 conversions') }}
                            </li>
                        @endforelse
                    </ul>

                    @auth
                        <a href="{{ route('subscription.plans') }}" class="mt-8 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ __('Choose :plan', ['plan' => __($plan->name)]) }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="mt-8 rounded-xl bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ $plan->effectiveTrialDays() > 0 ? __('Start free trial') : __('Get started') }}
                        </a>
                        <p class="mt-2 text-center text-xs text-slate-400">{!! __('Already have an account? :link', ['link' => '<a href="'.e(route('login')).'" class="underline">'.e(__('Log in')).'</a>']) !!}</p>
                    @endauth
                </div>
            @empty
                <p class="col-span-full text-center text-slate-500">{{ __('Packages are being set up — check back soon.') }}</p>
            @endforelse
        </div>

        <div class="mx-auto mt-16 max-w-2xl">
            <h2 class="text-xl font-bold text-slate-900">{{ __('Billing questions') }}</h2>
            <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                @foreach ($billingFaqs as [$q, $a])
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
