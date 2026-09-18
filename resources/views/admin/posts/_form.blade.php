@csrf

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input name="title" value="{{ old('title', $post->title) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('title')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">(URL id, e.g. <code>coinpoker-to-pt4-import</code>)</span></label>
        <input name="slug" value="{{ old('slug', $post->slug) }}" required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">
        <x-input-error :messages="$errors->get('slug')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Language</label>
        <select name="locale" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            @foreach (\App\Support\Locales::all() as $code => $settings)
                <option value="{{ $code }}" @selected(old('locale', $post->locale ?? \App\Support\Locales::default()) === $code)>{{ $settings['native'] }} ({{ $code }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('locale')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Translation of <span class="text-gray-400">(slug of the English post — leave blank for English posts)</span></label>
        <input name="translation_of" value="{{ old('translation_of', $post->translation_of) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">
        <x-input-error :messages="$errors->get('translation_of')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Excerpt <span class="text-gray-400">(shown on the blog list; falls back to the meta description)</span></label>
        <textarea name="excerpt" rows="2" maxlength="300"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('excerpt', $post->excerpt) }}</textarea>
        <x-input-error :messages="$errors->get('excerpt')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Body <span class="text-gray-400">(Markdown)</span></label>
        <textarea name="body" rows="18" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm font-mono">{{ old('body', $post->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Meta title <span class="text-gray-400">(optional — defaults to the title)</span></label>
        <input name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="160"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('meta_title')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Meta description <span class="text-gray-400">(optional — defaults to the excerpt)</span></label>
        <input name="meta_description" value="{{ old('meta_description', $post->meta_description) }}" maxlength="300"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('meta_description')" class="mt-1" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Published at <span class="text-gray-400">(blank + Published below = now; future date schedules it)</span></label>
        <input name="published_at" type="datetime-local"
               value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
        <x-input-error :messages="$errors->get('published_at')" class="mt-1" />
    </div>

    <div class="flex items-end pb-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published ?? false))
                   class="rounded border-gray-300 text-indigo-600">
            Published <span class="text-gray-400">— visible on the public blog</span>
        </label>
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('admin.posts.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
</div>
