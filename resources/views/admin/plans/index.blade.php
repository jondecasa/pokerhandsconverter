<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Packages &amp; pricing</h2>
            <a href="{{ route('admin.plans.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                New package
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div class="pc-card overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Package</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Stake</th>
                            <th class="px-4 py-3">Stripe price</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($plans as $plan)
                            <tr class="{{ $plan->is_active ? '' : 'opacity-50' }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $plan->name }}
                                        @if ($plan->is_highlighted) <span class="ml-1 text-xs text-indigo-600">★</span> @endif
                                    </div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $plan->slug }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $plan->priceLabel() }} <span class="text-gray-400">/ {{ $plan->interval }}</span></td>
                                <td class="px-4 py-3 text-gray-700">{{ $plan->stakesText() ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($plan->stripe_price_id)
                                        <span class="font-mono text-xs text-gray-500">{{ $plan->stripe_price_id }}</span>
                                    @else
                                        <span class="text-xs text-amber-600">not set</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $plan->is_visible ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $plan->is_visible ? 'Visible' : 'Hidden' }}
                                    </span>
                                    @unless ($plan->is_active)
                                        <span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Inactive</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.plans.edit', $plan) }}" class="text-indigo-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No packages yet. Create the first one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-400">
                <strong>Visible</strong> packages appear on the public pricing page and the in-app picker.
                <strong>Hidden</strong> packages are not listed but can still be subscribed to via their direct link
                <code>/subscribe/&lt;slug&gt;</code> (share it with a specific customer). Set <strong>Inactive</strong>
                to stop new sign-ups entirely.
            </p>
        </div>
    </div>
</x-app-layout>
