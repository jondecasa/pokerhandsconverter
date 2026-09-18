@if ($search !== '' || $locale !== '')
    <p class="text-sm text-gray-500">
        {{ $posts->count() }} of {{ $total }} {{ Str::plural('post', $total) }}@if ($search !== '') match &ldquo;{{ $search }}&rdquo;@endif @if ($locale !== '')in {{ \App\Support\Locales::setting($locale, 'native') }}@endif
    </p>
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
                        @if ($search !== '' || $locale !== '')
                            No posts match your filters.
                        @else
                            No posts yet. Create the first one.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
