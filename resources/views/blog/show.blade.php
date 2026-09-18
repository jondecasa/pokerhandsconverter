<x-marketing-layout
    :title="$post->metaTitle()"
    :description="$post->metaDescription()">

    @push('schema')
        {{ \App\Support\JsonLd::script([
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->metaDescription(),
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at->toAtomString(),
            'mainEntityOfPage' => route('blog.show', $post),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'PokerHandsConverter',
                'logo' => asset('images/icons/icon-512.png'),
            ],
        ]) }}
    @endpush

    <article class="mx-auto max-w-3xl px-6 py-16">
        @unless ($post->isPublished())
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                Preview only — this post is not live on the public blog yet.
            </div>
        @endunless

        <a href="{{ route('blog.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">&larr; Blog</a>

        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-slate-400">
            {{ $post->published_at?->format('F j, Y') ?? 'Not published' }} &middot; {{ $post->readingMinutes() }} min read
        </p>

        <div class="prose prose-slate mt-10 max-w-none">
            {!! $post->bodyHtml() !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="border-t border-slate-200 bg-slate-50">
            <div class="mx-auto max-w-3xl px-6 py-16">
                <h2 class="text-lg font-bold text-slate-900">More from the blog</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    @foreach ($related as $relatedPost)
                        <a href="{{ route('blog.show', $relatedPost) }}" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-indigo-300">
                            <div class="text-sm font-semibold text-slate-900">{{ $relatedPost->title }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $relatedPost->published_at->format('M j, Y') }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-indigo-600">
        <div class="mx-auto max-w-3xl px-6 py-12 text-center">
            <h2 class="text-2xl font-bold tracking-tight text-white">Ready to convert your hands?</h2>
            <div class="mt-6">
                @auth
                    <a href="{{ route('convert.create') }}" class="inline-flex rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Open the converter</a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Start free trial</a>
                @endauth
            </div>
        </div>
    </section>

</x-marketing-layout>
