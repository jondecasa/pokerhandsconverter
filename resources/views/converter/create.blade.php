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
                    <strong>PokerStars-formatted</strong> <code>.txt</code> you can import into Hold'em Manager 3,
                    PokerTracker 4 and similar trackers. Max {{ number_format($maxUploadKb / 1024, 0) }} MB per file.
                </p>

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
                            <option value="dual">PokerStars EU style: local time + "[… ET]" (default)</option>
                            <option value="et">US style: a single Eastern-time stamp</option>
                            <option value="keep">Leave the time and label exactly as CoinPoker wrote it</option>
                        </select>
                        <x-input-error :messages="$errors->get('timezone_mode')" class="mt-2" />
                    </div>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                        Convert
                    </button>
                </form>
            </div>

            @if ($recent->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Recent</h3>
                    @foreach ($recent as $conversion)
                        <div class="flex items-center justify-between py-2 border-b last:border-0 text-sm">
                            <a href="{{ route('conversions.show', $conversion) }}" class="text-indigo-600 hover:underline truncate">{{ $conversion->original_filename }}</a>
                            <span class="text-gray-500 shrink-0 ml-4">{{ $conversion->hand_count }} hands · {{ $conversion->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
