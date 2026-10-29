<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conversion logs</h2>
            <span class="text-sm text-gray-500">{{ number_format($total) }} {{ Str::plural('entry', $total) }} in total</span>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">{{ session('error') }}</div>
            @endif

            <p class="text-sm text-gray-600">
                Every file the converter turned down or crashed on: what the user was told and what we saw. Successful
                conversions are not listed here. Only the file name and size are kept, never its contents.
            </p>

            <form method="GET" action="{{ route('admin.failures.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1 sm:max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                    </svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search user, file, message or details…" autocomplete="off"
                           class="block w-full rounded-md border-gray-300 pl-9 text-sm shadow-sm">
                </div>
                <select name="reason" aria-label="Reason" onchange="this.form.requestSubmit()" class="rounded-md border-gray-300 text-sm shadow-sm sm:w-64">
                    <option value="">All reasons</option>
                    @foreach (\App\Models\ConversionFailure::REASONS as $code => $label)
                        <option value="{{ $code }}" @selected($reason === $code)>{{ $label }} ({{ $counts[$code] ?? 0 }})</option>
                    @endforeach
                </select>
                <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">Search</button>
                @if ($search !== '' || $reason !== '')
                    <a href="{{ route('admin.failures.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
                @endif
            </form>

            <div x-data="{
                    selected: [],
                    pageIds: @js($failures->pluck('id')->map(fn ($id) => (string) $id)->all()),
                    get allSelected() { return this.pageIds.length > 0 && this.selected.length === this.pageIds.length; },
                    toggleAll(checked) { this.selected = checked ? [...this.pageIds] : []; },
                    deleteSelected() {
                        const n = this.selected.length;
                        this.$dispatch('confirm-delete', {
                            action: @js(route('admin.failures.destroy-many')) + '?' + this.selected.map(id => 'ids[]=' + id).join('&'),
                            title: 'Delete ' + n + (n === 1 ? ' log entry?' : ' log entries?'),
                            message: 'The selected entries will be removed. This can’t be undone.',
                            confirmLabel: 'Delete ' + n,
                        });
                    },
                }" class="space-y-3">

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="deleteSelected()" :disabled="selected.length === 0"
                            class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-40">
                        Delete selected <span x-show="selected.length" x-text="'(' + selected.length + ')'"></span>
                    </button>
                    @if ($failures->total() > 0)
                        <button type="button"
                                @click="$dispatch('confirm-delete', @js([
                                    'action' => route('admin.failures.destroy-many', array_filter(['all' => 1, 'q' => $search, 'reason' => $reason])),
                                    'title' => ($search !== '' || $reason !== '') ? 'Delete all matching entries?' : 'Delete the whole log?',
                                    'message' => number_format($failures->total()).' log '.Str::plural('entry', $failures->total()).(($search !== '' || $reason !== '') ? ' matching the current filter' : '').' will be removed, including those on other pages. This can’t be undone.',
                                    'confirmLabel' => 'Delete '.number_format($failures->total()),
                                ]))"
                                class="inline-flex items-center rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-50">
                            Delete all {{ ($search !== '' || $reason !== '') ? 'matching' : '' }} ({{ number_format($failures->total()) }})
                        </button>
                    @endif
                </div>

                <div class="pc-card overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" aria-label="Select all on this page" :checked="allSelected" @change="toggleAll($event.target.checked)"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="px-4 py-3 whitespace-nowrap">When</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">File</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3">What the user saw</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        @forelse ($failures as $failure)
                            <tbody x-data="{ open: false }" class="divide-y divide-gray-100 border-t border-gray-100 first:border-t-0">
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" value="{{ $failure->id }}" x-model="selected" aria-label="Select entry {{ $failure->id }}"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ $failure->created_at->format('Y-m-d H:i') }}
                                        <div class="text-xs text-gray-400">{{ $failure->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($failure->user)
                                            <div class="font-medium text-gray-900">{{ $failure->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $failure->user->email }}</div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                        @if ($failure->ip)
                                            <div class="text-xs text-gray-400 font-mono">{{ $failure->ip }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="max-w-[16rem] break-all text-gray-900">{{ $failure->original_filename ?? '—' }}</div>
                                        <div class="text-xs text-gray-400">{{ $failure->sizeLabel() }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $failure->reasonClasses() }}">{{ $failure->reasonLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        <div class="max-w-md">{{ $failure->message }}</div>
                                        @if ($failure->details)
                                            <button type="button" @click="open = !open" class="mt-1 text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                                <span x-text="open ? 'Hide details' : 'Show details'"></span>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <button type="button" title="Delete" aria-label="Delete entry {{ $failure->id }}"
                                                    @click="$dispatch('confirm-delete', @js([
                                                        'action' => route('admin.failures.destroy', $failure),
                                                        'title' => 'Delete this log entry?',
                                                        'message' => 'The entry for “'.($failure->original_filename ?? 'unknown file').'” will be removed. This can’t be undone.',
                                                        'confirmLabel' => 'Delete entry',
                                                    ]))"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @if ($failure->details)
                                    <tr x-show="open" x-cloak class="bg-slate-50">
                                        <td></td>
                                        <td colspan="6" class="px-4 pb-4 pt-1">
                                            <pre class="max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-md bg-white p-3 text-xs text-gray-700 ring-1 ring-gray-200">{{ $failure->details }}</pre>
                                            @if ($failure->user_agent)
                                                <p class="mt-2 break-all text-xs text-gray-400">{{ $failure->user_agent }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        @if ($search !== '' || $reason !== '')
                                            No entries match your filters.
                                        @else
                                            Nothing logged yet. Rejected or failed uploads will show up here.
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                <div>{{ $failures->links() }}</div>
            </div>
        </div>
    </div>

    <x-confirm-modal />
</x-app-layout>
