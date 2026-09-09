<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Subscription status --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Subscription</h3>
                        @if ($subscribed)
                            <p class="text-sm text-gray-600 mt-1">
                                @if ($onTrial)
                                    On free trial until {{ optional($subscription->trial_ends_at)->toFormattedDateString() }}.
                                @elseif ($onGracePeriod)
                                    Cancelled — access ends {{ optional($subscription->ends_at)->toFormattedDateString() }}.
                                @else
                                    Active. Thanks for supporting PokerCoinverter.
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-1">No active subscription. Subscribe to unlock the converter.</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($subscribed)
                            <a href="{{ route('billing') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Manage billing</a>
                            @if ($onGracePeriod)
                                <form method="POST" action="{{ route('subscription.resume') }}">@csrf
                                    <button class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-500">Resume</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('subscription.cancel') }}" onsubmit="return confirm('Cancel at the end of the billing period?')">@csrf
                                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">Cancel</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">See plans</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['files']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Files converted</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['hands']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Hands converted</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center">
                    <a href="{{ route('convert.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                        Convert a file &rarr;
                    </a>
                </div>
            </div>

            {{-- Recent conversions --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent conversions</h3>
                @forelse ($recent as $conversion)
                    <div class="flex items-center justify-between py-2 border-b last:border-0 text-sm">
                        <div class="truncate">
                            <a href="{{ route('conversions.show', $conversion) }}" class="text-indigo-600 hover:underline">{{ $conversion->original_filename }}</a>
                            <span class="text-gray-400">· {{ $conversion->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-gray-500 shrink-0 ml-4">
                            {{ $conversion->hand_count }} hands
                            @if ($conversion->warning_count) · <span class="text-amber-600">{{ $conversion->warning_count }} warnings</span> @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Nothing converted yet.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
