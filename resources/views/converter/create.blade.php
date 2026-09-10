<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Convert a hand history</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Upload a <strong>CoinPoker</strong> hand-history <code>.txt</code> file. You will get back a
                    <code>.txt</code> in the format <strong>PokerTracker&nbsp;4</strong> reads (Hold'em Manager&nbsp;3
                    and similar trackers import it too). Max {{ number_format($maxUploadKb / 1024, 0) }} MB per file.
                </p>

                <div class="mb-4 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-gray-600">
                        Hero name:
                        <span class="ml-1 font-medium text-gray-900">{{ $heroName }}</span>
                        @if ($heroName === 'Hero')
                            <a href="{{ route('profile.edit') }}" class="ml-1.5 text-indigo-600 hover:underline">set your CoinPoker ID</a>
                        @endif
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-gray-600">
                        Package covers:
                        <span class="ml-1 font-medium text-gray-900">{{ $stakesCap ? 'up to '.$stakesCap : 'all stakes' }}</span>
                    </span>
                </div>

                <form method="POST" action="{{ route('convert.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700">Hand history file</label>
                        <input id="file" name="file" type="file" accept=".txt,text/plain" required
                               class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer focus:outline-none" />
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>

                    <div>
                        <label for="timezone_mode" class="block text-sm font-medium text-gray-700">Timezone handling</label>
                        <select id="timezone_mode" name="timezone_mode"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="dual">European style: local time + "[… ET]" (default)</option>
                            <option value="et">US style: a single Eastern-time stamp</option>
                            <option value="keep">Leave the time and label exactly as CoinPoker wrote it</option>
                        </select>
                        <x-input-error :messages="$errors->get('timezone_mode')" class="mt-2" />
                    </div>

                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium text-gray-700">Include in the output</legend>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="include_bomb_pots" value="0">
                            <input type="checkbox" name="include_bomb_pots" value="1" @checked($includeBombPots)
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Bomb pots
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="include_splash_pots" value="0">
                            <input type="checkbox" name="include_splash_pots" value="1" @checked($includeSplashPots)
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Splash pots
                        </label>
                        <p class="text-xs text-gray-400">Your choice is saved for next time.</p>
                    </fieldset>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                        Convert
                    </button>
                </form>
            </div>

            @if ($recent->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6"
                     x-data="{ pendingAction: null, pendingName: '' }">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Recent</h3>
                    @foreach ($recent as $conversion)
                        <div class="flex items-center justify-between gap-4 py-2 border-b last:border-0 text-sm">
                            <a href="{{ route('conversions.show', $conversion) }}" class="text-indigo-600 hover:underline truncate">{{ $conversion->original_filename }}</a>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-gray-500">{{ $conversion->hand_count }} hands · {{ $conversion->created_at->diffForHumans() }}</span>
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
                    @endforeach

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
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
