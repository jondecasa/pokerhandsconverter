<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit package — {{ $plan->name }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                    @method('PUT')
                    @include('admin.plans._form', ['submitLabel' => 'Save changes'])
                </form>
            </div>

            <div class="pc-card p-6">
                <h3 class="text-sm font-semibold text-gray-900">Danger zone</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Deleting a package does not touch existing subscriptions on it — Stripe keeps billing them.
                    Prefer un-checking <strong>Active</strong> to retire a package.
                </p>
                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="mt-3"
                      onsubmit="return confirm('Delete the &quot;{{ $plan->name }}&quot; package?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 text-sm rounded-md hover:bg-red-50">
                        Delete package
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
