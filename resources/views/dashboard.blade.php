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
            <div class="pc-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Subscription</h3>
                        @if ($subscribed)
                            <p class="text-sm text-gray-600 mt-1">
                                @if ($onFreePlan)
                                    Active on a free package.
                                @elseif ($onTrial)
                                    On free trial until {{ optional($subscription->trial_ends_at)->toFormattedDateString() }}.
                                @elseif ($onGracePeriod)
                                    Cancelled — access ends {{ optional($subscription->ends_at)->toFormattedDateString() }}.
                                @else
                                    Active. Thanks for supporting PokerHandsConverter.
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-gray-600 mt-1">No active subscription. Subscribe to unlock the converter.</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($subscribed)
                            @unless ($onFreePlan)
                                <a href="{{ route('billing') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Manage billing</a>
                            @endunless
                            @if ($onGracePeriod)
                                <form method="POST" action="{{ route('subscription.resume') }}">@csrf
                                    <button class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-500">Resume</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('subscription.cancel') }}" onsubmit="return confirm(@js($onFreePlan ? 'Remove the free package? You will lose access to the converter.' : 'Cancel at the end of the billing period?'))">@csrf
                                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm rounded-md hover:bg-gray-50">{{ $onFreePlan ? 'Remove package' : 'Cancel' }}</button>
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
                <div class="pc-card p-6">
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['files']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Files converted</div>
                </div>
                <div class="pc-card p-6">
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['hands']) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Hands converted</div>
                </div>
                <div class="pc-card p-6 flex items-center">
                    <a href="{{ route('convert.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">
                        Convert a file &rarr;
                    </a>
                </div>
            </div>

            {{-- Recent conversions --}}
            <div class="pc-card p-6"
                 x-data="{ pendingAction: null, pendingName: '' }">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent conversions</h3>
                @forelse ($recent as $conversion)
                    <div class="flex items-center justify-between gap-4 py-2 border-b last:border-0 text-sm">
                        <div class="truncate">
                            <a href="{{ route('conversions.show', $conversion) }}" class="text-indigo-600 hover:underline">{{ $conversion->original_filename }}</a>
                            <span class="text-gray-400">· {{ $conversion->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-gray-500">
                                {{ $conversion->hand_count }} hands
                                @if ($conversion->warning_count) · <span class="text-amber-600">{{ $conversion->warning_count }} warnings</span> @endif
                            </span>
                            <button type="button"
                                    title="Delete conversion"
                                    x-on:click="pendingAction='{{ route('conversions.destroy', $conversion) }}'; pendingName=@js($conversion->original_filename); $dispatch('open-modal', 'confirm-conversion-deletion')"
                                    class="inline-flex items-center justify-center h-8 w-8 rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Nothing converted yet.</p>
                @endforelse

                @if ($recent->isNotEmpty())
                    <x-modal name="confirm-conversion-deletion" focusable>
                        <form method="POST" x-bind:action="pendingAction" class="p-6">
                            @csrf
                            @method('DELETE')

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Delete this conversion?</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        <span class="font-medium text-gray-900" x-text="pendingName"></span> and its converted
                                        file will be permanently removed. This cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                <x-danger-button>Delete conversion</x-danger-button>
                            </div>
                        </form>
                    </x-modal>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
