<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Subscription</h2>
        <p class="mt-1 text-sm text-gray-600">
            @if (! $subscribed)
                No active subscription. Subscribe to unlock the converter.
            @elseif ($onFreePlan)
                Active on a free package.
            @elseif ($onTrial)
                On free trial until {{ optional($subscription->trial_ends_at)->toFormattedDateString() }}.
            @elseif ($onGracePeriod)
                Cancelled — access ends {{ optional($subscription->ends_at)->toFormattedDateString() }}.
            @else
                Active. Thanks for supporting PokerHandsConverter.
            @endif
        </p>
    </header>

    @if (session('status') && session('status') !== 'profile-updated')
        <p class="mt-3 text-sm text-green-700">{{ session('status') }}</p>
    @endif

    <div class="mt-4 flex flex-wrap items-center gap-3">
        @if (! $subscribed)
            <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-500">See plans</a>
        @else
            @unless ($onFreePlan)
                <a href="{{ route('billing') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Manage billing</a>
            @endunless
            @if ($onGracePeriod)
                <form method="POST" action="{{ route('subscription.resume') }}">@csrf
                    <button class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-500">Resume</button>
                </form>
            @endif
        @endif
    </div>

    {{-- Cancelling is kept out of sight on purpose: a closed, low-key fold under the billing button. --}}
    @if ($subscribed && ! $onGracePeriod)
        <details class="mt-6 text-xs text-gray-400">
            <summary class="w-fit cursor-pointer select-none list-none hover:text-gray-500">More options</summary>
            <form method="POST" action="{{ route('subscription.cancel') }}" class="mt-2"
                  onsubmit="return confirm(@js($onFreePlan ? 'Remove the free package? You will lose access to the converter.' : 'Cancel at the end of the billing period?'))">@csrf
                <button class="underline hover:text-gray-600">{{ $onFreePlan ? 'Remove package' : 'Cancel subscription' }}</button>
            </form>
        </details>
    @endif
</section>
