<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subscribers</h2>
    </x-slot>

    <div class="py-6 sm:py-12" x-data="{ tab: '{{ $defaultTab }}' }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $t)
                    <button type="button" @click="tab = @js($t['plan']->slug)"
                            :class="tab === @js($t['plan']->slug) ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium">
                        {{ $t['plan']->name }}
                        <span class="ml-1 text-xs text-gray-400">({{ $t['subscriptions']->count() }})</span>
                    </button>
                @endforeach
                @if ($other->isNotEmpty())
                    <button type="button" @click="tab = 'other'"
                            :class="tab === 'other' ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                            class="rounded-lg border px-3 py-1.5 text-sm font-medium">
                        Other <span class="ml-1 text-xs text-gray-400">({{ $other->count() }})</span>
                    </button>
                @endif
            </div>

            @forelse ($tabs as $t)
                <div x-show="tab === @js($t['plan']->slug)" x-cloak>
                    @include('admin.subscribers._table', ['subscriptions' => $t['subscriptions']])
                </div>
            @empty
                <p class="text-sm text-gray-500">Create a package first — see Admin &rarr; Packages.</p>
            @endforelse

            @if ($other->isNotEmpty())
                <div x-show="tab === 'other'" x-cloak>
                    <p class="mb-2 text-xs text-gray-500">Subscriptions whose Stripe price doesn't match any current package (renamed, deleted, or reconfigured).</p>
                    @include('admin.subscribers._table', ['subscriptions' => $other])
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
