<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conversion result</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            {{-- Upload summary --}}
            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                <div class="pc-card p-3 text-center sm:p-6">
                    <div class="text-xl font-bold text-gray-900 sm:text-3xl">{{ number_format($conversion->hand_count) }}</div>
                    <div class="mt-1 text-xs text-gray-500 sm:text-sm">Hands</div>
                </div>
                <div class="pc-card p-3 text-center sm:p-6">
                    <div class="text-xl font-bold sm:text-3xl {{ $conversion->splash_pots ? 'text-indigo-600' : 'text-gray-900' }}">{{ number_format($conversion->splash_pots) }}</div>
                    <div class="mt-1 text-xs text-gray-500 sm:text-sm">Splash pots</div>
                </div>
                <div class="pc-card p-3 text-center sm:p-6">
                    <div class="text-xl font-bold sm:text-3xl {{ $conversion->bomb_pots ? 'text-indigo-600' : 'text-gray-900' }}">{{ number_format($conversion->bomb_pots) }}</div>
                    <div class="mt-1 text-xs text-gray-500 sm:text-sm">Bomb pots</div>
                </div>
            </div>

            <div class="pc-card p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 break-words">{{ $conversion->original_filename }}</h3>
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
                <details class="group rounded-lg border border-amber-200 bg-amber-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-4 font-semibold text-amber-800">
                        <span>{{ count($conversion->warnings) }} warning(s)</span>
                        <svg class="h-5 w-5 shrink-0 text-amber-500 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <ul class="list-disc list-inside text-sm text-amber-800 space-y-1 px-4 pb-4">
                        @foreach ($conversion->warnings as $warning)
                            <li>@if(!is_null($warning['hand'])) <span class="font-mono">#{{ $warning['hand'] }}</span> — @endif {{ $warning['message'] }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif

            @if ($preview)
                <div class="pc-card p-4 sm:p-6">
                    <h4 class="font-semibold text-gray-900 mb-2">Preview</h4>
                    <pre class="text-xs bg-gray-900 text-gray-100 rounded-lg p-4 overflow-x-auto whitespace-pre">{{ $preview }}</pre>
                </div>
            @endif

            {{-- Report a PT4 import problem --}}
            <div class="pc-card p-4 sm:p-6">
                <h4 class="font-semibold text-gray-900">Got an error importing this into PT4?</h4>
                <p class="mt-1 text-sm text-gray-500">
                    Tell us what happened and we'll look into it — this sends us the filename and hand count above so we can debug it.
                </p>
                <form method="POST" action="{{ route('conversions.feedback', $conversion) }}" class="mt-3">
                    @csrf
                    <label for="feedback-message" class="sr-only">What went wrong</label>
                    <textarea id="feedback-message" name="message" rows="3" required minlength="10" maxlength="3000"
                              placeholder="e.g. PokerTracker rejected hand #130114200045 with &quot;Invalid pot size&quot;"
                              class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                    <button type="submit"
                            class="mt-3 inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">
                        Send feedback
                    </button>
                </form>
            </div>

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
