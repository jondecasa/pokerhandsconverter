<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blog posts</h2>
            <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                New post
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div x-data="{
                    filtered: @js($search !== '' || $locale !== ''),
                    timer: null,
                    controller: null,
                    async refresh() {
                        clearTimeout(this.timer);
                        this.controller?.abort();
                        this.controller = new AbortController();
                        const params = new URLSearchParams(new FormData(this.$refs.form));
                        for (const [key, value] of [...params]) if (value.trim() === '') params.delete(key);
                        this.filtered = [...params.keys()].length > 0;
                        const url = this.$refs.form.action + (this.filtered ? '?' + params : '');
                        try {
                            const response = await fetch(url, { signal: this.controller.signal });
                            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                            const fresh = page.querySelector('[data-results]');
                            if (! fresh) return window.location.assign(url);
                            this.$refs.results.innerHTML = fresh.innerHTML;
                            history.replaceState(null, '', url);
                        } catch (error) {
                            if (error.name !== 'AbortError') window.location.assign(url);
                        }
                    },
                    typing() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.refresh(), 250);
                    },
                    clear() {
                        this.$refs.form.q.value = '';
                        this.$refs.form.locale.value = '';
                        this.refresh();
                    },
                }" class="space-y-4 sm:space-y-6">

                <form x-ref="form" method="GET" action="{{ route('admin.posts.index') }}" @submit.prevent="refresh()" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative flex-1 sm:max-w-md">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                        </svg>
                        <input type="search" name="q" value="{{ $search }}" placeholder="Search title, slug or excerpt…" autocomplete="off"
                               @input="typing()"
                               class="block w-full rounded-md border-gray-300 pl-9 text-sm shadow-sm">
                    </div>
                    <select name="locale" aria-label="Language" @change="refresh()" class="rounded-md border-gray-300 text-sm shadow-sm sm:w-48">
                        <option value="">All languages</option>
                        @foreach (\App\Support\Locales::all() as $code => $settings)
                            <option value="{{ $code }}" @selected($locale === $code)>{{ $settings['native'] }}</option>
                        @endforeach
                    </select>
                    <noscript>
                        <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">Search</button>
                    </noscript>
                    <a href="{{ route('admin.posts.index') }}" @click.prevent="clear()" x-show="filtered" @if ($search === '' && $locale === '') x-cloak @endif
                       class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
                </form>

                <div x-ref="results" data-results class="space-y-4 sm:space-y-6">
                    @include('admin.posts._results')
                </div>
            </div>
        </div>
    </div>

    <x-confirm-modal />
</x-app-layout>
