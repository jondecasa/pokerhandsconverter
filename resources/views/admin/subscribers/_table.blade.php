<div class="pc-card overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Started</th>
                <th class="px-4 py-3">Trial ends</th>
                <th class="px-4 py-3">Ends / renews</th>
                <th class="px-4 py-3">Stripe subscription</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($subscriptions as $subscription)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $subscription->user?->name ?? 'Unknown user' }}</div>
                        <div class="text-xs text-gray-400">{{ $subscription->user?->email ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($subscription->onTrial())
                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Trial</span>
                        @elseif ($subscription->onGracePeriod())
                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Canceling</span>
                        @elseif ($subscription->ended())
                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Canceled</span>
                        @elseif ($subscription->active())
                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Active</span>
                        @else
                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">{{ ucfirst($subscription->stripe_status) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ $subscription->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $subscription->trial_ends_at?->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $subscription->ends_at?->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-400">{{ $subscription->stripe_id }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No subscribers on this package yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
