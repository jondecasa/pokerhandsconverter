<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz Ranges</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            <div class="pc-card p-5 text-sm text-gray-600">
                Pick a study and answer {{ $questionCount }} random spots: you get a hand in a situation and choose the action your chart prescribes.
                A hand the chart leaves unpainted is a <span class="font-semibold">fold</span>. At the end you see your score and where you slip up most.
            </div>

            @if ($studies->isEmpty())
                <div class="pc-card p-6 text-sm text-gray-500">No range studies have been published yet — check back soon.</div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($studies as $study)
                        @if ($study->quiz_scenarios_count > 0)
                            <a href="{{ route('quiz.show', $study) }}" class="pc-card p-5 hover:border-indigo-300">
                                <h3 class="text-base font-semibold text-gray-900">{{ $study->name }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $study->quiz_scenarios_count }} {{ Str::plural('spot type', $study->quiz_scenarios_count) }} in the pool</p>
                                <p class="mt-3 text-sm font-medium text-indigo-600">Start quiz &rarr;</p>
                            </a>
                        @else
                            <div class="pc-card p-5 opacity-60">
                                <h3 class="text-base font-semibold text-gray-900">{{ $study->name }}</h3>
                                <p class="mt-1 text-sm text-gray-500">Not available for the quiz yet.</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
