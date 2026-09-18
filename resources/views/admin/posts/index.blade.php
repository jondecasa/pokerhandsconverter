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

            <form method="GET" action="{{ route('admin.posts.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1 sm:max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                    </svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search title, slug or excerpt…" autocomplete="off"
                           class="block w-full rounded-md border-gray-300 pl-9 text-sm shadow-sm">
                </div>
                <div class="flex items-center gap-3">
                    <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">Search</button>
                    @if ($search !== '')
                        <a href="{{ route('admin.posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
                    @endif
                </div>
            </form>

            @if ($search !== '')
                <p class="text-sm text-gray-500">{{ $posts->count() }} of {{ $total }} {{ Str::plural('post', $total) }} match &ldquo;{{ $search }}&rdquo;.</p>
            @endif

            <div class="pc-card overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Published at</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($posts as $post)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $post->title }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $post->slug }} <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 font-sans text-[10px] font-semibold uppercase text-slate-500">{{ $post->locale }}</span></div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($post->isPublished())
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Published</span>
                                    @elseif ($post->is_published)
                                        <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Scheduled</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Draft</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.posts.edit', $post) }}" title="Edit" aria-label="Edit {{ $post->title }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                            </svg>
                                        </a>
                                        <button type="button" title="Delete" aria-label="Delete {{ $post->title }}"
                                                @click="$dispatch('confirm-delete', @js([
                                                    'action' => route('admin.posts.destroy', $post),
                                                    'title' => 'Delete this post?',
                                                    'message' => '“'.$post->title.'” and its public URL will be removed. This can’t be undone.',
                                                    'confirmLabel' => 'Delete post',
                                                ]))"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-600 text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                    @if ($search !== '')
                                        No posts match &ldquo;{{ $search }}&rdquo;.
                                    @else
                                        No posts yet. Create the first one.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-confirm-modal />
</x-app-layout>
