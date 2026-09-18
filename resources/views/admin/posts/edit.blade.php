<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit post — {{ $post->title }}</h2>
            @if ($post->isPublished())
                <a href="{{ route('blog.show', $post) }}" target="_blank" class="text-sm text-indigo-600 hover:underline">View live &rarr;</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.posts.update', $post) }}">
                    @method('PUT')
                    @include('admin.posts._form', ['submitLabel' => 'Save changes'])
                </form>
            </div>

            <div class="pc-card p-6">
                <h3 class="text-sm font-semibold text-gray-900">Danger zone</h3>
                <p class="text-sm text-gray-500 mt-1">This removes the post and its public URL immediately.</p>
                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="mt-3"
                      onsubmit="return confirm('Delete the &quot;{{ $post->title }}&quot; post?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 text-sm rounded-md hover:bg-red-50">
                        Delete post
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
