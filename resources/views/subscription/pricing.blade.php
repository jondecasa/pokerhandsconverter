<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pricing</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            @if ($subscribed)
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    You already have an active subscription.
                    <a href="{{ route('billing') }}" class="underline">Manage billing</a> or
                    <a href="{{ route('convert.create') }}" class="underline">start converting</a>.
                </div>
            @elseif ($trialUsed)
                <div class="bg-slate-50 border border-slate-200 text-slate-600 rounded-lg p-4 text-sm">
                    You've already used your free trial on this account, so a new subscription starts billing right away.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse ($plans as $plan)
                    <div class="pc-card p-8 flex flex-col">
                        <h3 class="text-xl font-bold text-gray-900">{{ $plan->name }}</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-extrabold text-gray-900">{{ $plan->priceLabel() }}</span>
                            @unless ($plan->isFree())<span class="text-gray-500">/ {{ $plan->interval }}</span>@endunless
                        </div>
                        @if ($plan->stakesText())
                            <div class="mt-2 inline-flex w-max rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ $plan->stakesText() }}</div>
                        @endif
                        <p class="text-sm text-gray-600 mt-3">{{ $plan->description }}</p>

                        <ul class="mt-4 space-y-2 text-sm text-gray-700 flex-1">
                            @forelse ($plan->featureList() as $feature)
                                <li>✓ {{ $feature }}</li>
                            @empty
                                <li>✓ Unlimited CoinPoker → PokerTracker 4 conversions</li>
                            @endforelse
                        </ul>

                        @if ($currentPrice && $currentPrice === $plan->priceKey())
                            <span class="mt-6 inline-flex justify-center px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-md">Current package</span>
                        @else
                            <form method="POST" action="{{ route('subscription.checkout', $plan) }}" class="mt-6">
                                @csrf
                                <button class="w-full inline-flex justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                    @if ($plan->isFree())
                                        Get it free
                                    @elseif ($plan->effectiveTrialDays() > 0 && ! $subscribed && ! $trialUsed)
                                        Start {{ $plan->effectiveTrialDays() }}-day free trial
                                    @else
                                        Choose {{ $plan->name }}
                                    @endif
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">No packages available yet.</p>
                @endforelse
            </div>

            <p class="text-xs text-gray-400 text-center">
                Payments are processed securely by Stripe. PokerHandsConverter never sees your card details.
            </p>
        </div>
    </div>
</x-app-layout>
