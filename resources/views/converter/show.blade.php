<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conversion result</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            {{-- Upload summary --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-gray-900">{{ number_format($conversion->hand_count) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Hands</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold {{ $conversion->splash_pots ? 'text-indigo-600' : 'text-gray-900' }}">{{ number_format($conversion->splash_pots) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Splash pots</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold {{ $conversion->bomb_pots ? 'text-indigo-600' : 'text-gray-900' }}">{{ number_format($conversion->bomb_pots) }}</div>
                    <div class="text-sm text-gray-500 mt-1">Bomb pots</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $conversion->original_filename }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $conversion->hand_count }} hands ·
                            {{ number_format($conversion->input_bytes / 1024, 1) }} KB in ·
                            {{ $conversion->created_at->toDayDateTimeString() }}
                        </p>
                        @if ($conversion->format_breakdown)
                            <p class="text-sm text-gray-500 mt-1">
                                @foreach ($conversion->format_breakdown as $format => $count)
                                    <span class="inline-block bg-gray-100 rounded px-2 py-0.5 mr-1">{{ $format }}: {{ $count }}</span>
                                @endforeach
                            </p>
                        @endif
                    </div>
                    @if ($conversion->outputExists())
                        <a href="{{ route('conversions.download', $conversion) }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                            Download {{ $conversion->downloadName() }}
                        </a>
                    @else
                        <span class="text-sm text-red-600">Output file is no longer available.</span>
                    @endif
                </div>
            </div>

            @if ($conversion->warnings)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <h4 class="font-semibold text-amber-800 mb-2">{{ count($conversion->warnings) }} warning(s)</h4>
                    <ul class="list-disc list-inside text-sm text-amber-800 space-y-1">
                        @foreach ($conversion->warnings as $warning)
                            <li>@if(!is_null($warning['hand'])) <span class="font-mono">#{{ $warning['hand'] }}</span> — @endif {{ $warning['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($preview)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="font-semibold text-gray-900 mb-2">Preview</h4>
                    <pre class="text-xs bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto whitespace-pre">{{ $preview }}</pre>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('convert.create') }}" class="inline-block text-sm text-indigo-600 hover:underline">&larr; Convert another file</a>

                <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-conversion-deletion')">
                    <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete conversion
                </x-danger-button>
            </div>

            <x-modal name="confirm-conversion-deletion" focusable>
                <form method="POST" action="{{ route('conversions.destroy', $conversion) }}" class="p-6">
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
                                <span class="font-medium text-gray-900">{{ $conversion->original_filename }}</span> and its
                                converted file will be permanently removed. This cannot be undone.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete conversion</x-danger-button>
                    </div>
                </form>
            </x-modal>

        </div>
    </div>
</x-app-layout>
