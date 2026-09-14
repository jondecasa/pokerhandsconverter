<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit study — {{ $study->name }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">{{ session('status') }}</div>
            @endif

            <div class="pc-card p-6">
                <form method="POST" action="{{ route('admin.range-studies.update', $study) }}">
                    @method('PUT')
                    @include('admin.range-studies._form', ['submitLabel' => 'Save changes'])
                </form>
            </div>

            <div class="pc-card p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Scenarios</h3>
                    <a href="{{ route('admin.range-studies.scenarios.create', $study) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-500">
                        Add scenario
                    </a>
                </div>

                @forelse ($navigation as $groupLabel => $rows)
                    <div class="mt-4 rounded-lg border border-gray-200 p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $groupLabel }}</h4>
                        <div class="mt-3 space-y-2">
                            @foreach ($rows as $rowLabel => $scenarios)
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="w-16 shrink-0 font-medium text-gray-700">{{ $rowLabel }}</span>
                                    @foreach ($scenarios as $scenario)
                                        <span class="inline-flex items-center gap-1 rounded-md border border-gray-200 pl-2 pr-1 py-1">
                                            <a href="{{ route('admin.range-scenarios.edit', $scenario) }}" class="text-indigo-600 hover:underline">
                                                {{ $scenario->button_label }}
                                            </a>
                                            @if ($scenario->is_default)
                                                <span class="text-[10px] font-semibold uppercase text-emerald-600">default</span>
                                            @endif
                                            <form method="POST" action="{{ route('admin.range-scenarios.destroy', $scenario) }}"
                                                  onsubmit="return confirm('Delete the &quot;{{ $scenario->button_label }}&quot; scenario?')">
                                                @csrf @method('DELETE')
                                                <button class="px-1 text-gray-400 hover:text-red-600" title="Delete">&times;</button>
                                            </form>
                                        </span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="mt-4 text-sm text-gray-500">No scenarios yet — add the first one.</p>
                @endforelse
            </div>

            <div class="pc-card p-6">
                <h3 class="text-sm font-semibold text-gray-900">Danger zone</h3>
                <p class="text-sm text-gray-500 mt-1">Deletes this study and every scenario in it. This cannot be undone.</p>
                <form method="POST" action="{{ route('admin.range-studies.destroy', $study) }}" class="mt-3"
                      onsubmit="return confirm('Delete the &quot;{{ $study->name }}&quot; study and all its scenarios?')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 text-sm rounded-md hover:bg-red-50">
                        Delete study
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
