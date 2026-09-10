<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New package</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.plans.store') }}">
                    @include('admin.plans._form', ['submitLabel' => 'Create package'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
