<x-marketing-layout
    title="Blog — PokerHandsConverter"
    description="Guides and notes on converting CoinPoker hand histories for PokerTracker 4, Hold'em Manager 3 and Hand2Note.">

    <section class="mx-auto max-w-5xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">Blog</h1>
        </div>

        <div class="mx-auto mt-14 grid max-w-4xl gap-6 sm:grid-cols-2">
            @forelse ($posts as $post)
                <a href="{{ route('blog.show', $post) }}" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 hover:border-indigo-300 hover:shadow-sm">
                    <span class="text-xs font-medium text-slate-400">
                        {{ $post->published_at->format('M j, Y') }} &middot; {{ $post->readingMinutes() }} min read
                    </span>
                    <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $post->title }}</h2>
                    @if ($post->excerpt)
                        <p class="mt-2 text-sm text-slate-600 flex-1">{{ $post->excerpt }}</p>
                    @endif
                    <span class="mt-4 text-sm font-semibold text-indigo-600">Read more &rarr;</span>
                </a>
            @empty
                <p class="col-span-full text-center text-slate-500">No posts yet — check back soon.</p>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <div class="mt-10">{{ $posts->links('pagination.brand') }}</div>
        @endif
    </section>

</x-marketing-layout>
