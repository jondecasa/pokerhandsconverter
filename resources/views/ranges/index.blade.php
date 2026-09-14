<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Preflop ranges</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if ($studies->isEmpty())
                <div class="pc-card p-6 text-sm text-gray-500">No range studies have been published yet — check back soon.</div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($studies as $study)
                        <a href="{{ route('ranges.show', $study) }}" class="pc-card p-5 hover:border-indigo-300">
                            <h3 class="text-base font-semibold text-gray-900">{{ $study->name }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $study->scenarios_count }} scenario(s)</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
