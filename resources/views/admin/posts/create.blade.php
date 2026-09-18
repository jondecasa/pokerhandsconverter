<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New post</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.posts.store') }}">
                    @include('admin.posts._form', ['submitLabel' => 'Create post'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
