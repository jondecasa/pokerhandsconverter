<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pricing</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($plans as $key => $plan)
                    <div class="bg-white shadow-sm sm:rounded-lg p-8 flex flex-col">
                        <h3 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h3>
                        <div class="mt-3">
                            <span class="text-4xl font-extrabold text-gray-900">{{ $plan['currency'] === 'USD' ? '$' : '' }}{{ $plan['amount'] }}</span>
                            <span class="text-gray-500">/ {{ $plan['interval'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-3">{{ $plan['blurb'] }}</p>

                        <ul class="mt-4 space-y-2 text-sm text-gray-700 flex-1">
                            <li>✓ Unlimited CoinPoker → PokerStars conversions</li>
                            <li>✓ Cash &amp; tournament hand histories</li>
                            <li>✓ Re-download past conversions</li>
                            <li>✓ Cancel anytime from the billing portal</li>
                        </ul>

                        @if ($currentPlan === $plan['price_id'] && $plan['price_id'])
                            <span class="mt-6 inline-flex justify-center px-4 py-2 bg-gray-100 text-gray-600 text-sm rounded-md">Current plan</span>
                        @else
                            <form method="POST" action="{{ route('subscription.checkout', $key) }}" class="mt-6">
                                @csrf
                                <button class="w-full inline-flex justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                                    @if ($trialDays > 0 && ! $subscribed)
                                        Start {{ $trialDays }}-day free trial
                                    @else
                                        Choose {{ $plan['name'] }}
                                    @endif
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-gray-400 text-center">
                Payments are processed securely by Stripe. PokerCoinverter never sees your card details.
            </p>
        </div>
    </div>
</x-app-layout>
