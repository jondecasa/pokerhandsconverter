<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit scenario — {{ $study->name }} / {{ $scenario->button_label }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.range-scenarios.update', $scenario) }}">
                    @method('PUT')
                    @include('admin.range-scenarios._form', ['submitLabel' => 'Save changes'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
