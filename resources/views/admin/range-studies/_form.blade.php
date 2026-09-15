@csrf

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input name="name" value="{{ old('name', $study->name) }}" required placeholder="e.g. 6MAX 100BB"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">(URL id)</span></label>
        <input name="slug" value="{{ old('slug', $study->slug) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">
        <x-input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Sort order <span class="text-gray-400">(lower shows first)</span></label>
        <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $study->sort_order ?? 0) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
    </div>
</div>

@if ($study->exists && $study->rowLabels()->isNotEmpty())
    <div class="mt-8">
        <label class="block text-sm font-medium text-gray-700">Row colors</label>
        <p class="mt-1 text-xs text-gray-500">Background color for each position's buttons (EP, MP, CO...) — used everywhere that row appears in the navigation.</p>
        <div class="mt-3 flex flex-wrap gap-4">
            @foreach ($study->rowLabels()->sort() as $rowLabel)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="color" name="row_colors[{{ $rowLabel }}]"
                           value="{{ old('row_colors.'.$rowLabel, $study->rowColor($rowLabel)) }}"
                           class="h-8 w-10 rounded border-gray-300 p-0">
                    {{ $rowLabel }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('row_colors.*')" class="mt-1" />
    </div>
@endif

<div class="mt-8 flex items-center gap-3">
    <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.range-studies.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
</div>
