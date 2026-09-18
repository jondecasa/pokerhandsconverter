<?php

namespace App\Http\Requests;

use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $postId = $this->route('post')?->id;
        $locale = $this->input('locale') ?: Locales::default();

        return [
            'title' => ['required', 'string', 'max:150'],
            // A slug is unique within its language: a translation can reuse the original's.
            'slug' => ['required', 'string', 'max:150', 'alpha_dash', Rule::unique('posts', 'slug')->where('locale', $locale)->ignore($postId)],
            'locale' => ['nullable', Rule::in(Locales::codes())],
            'translation_of' => ['nullable', 'string', 'max:150'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function postData(): array
    {
        $data = $this->safe()->only(['title', 'slug', 'excerpt', 'body', 'meta_title', 'meta_description']);
        $data['locale'] = $this->input('locale') ?: Locales::default();
        $data['translation_of'] = Locales::isDefault($data['locale']) ? null : $this->input('translation_of');
        $data['is_published'] = $this->boolean('is_published');
        $data['published_at'] = $this->filled('published_at')
            ? $this->date('published_at')
            : ($data['is_published'] ? now() : null);

        return $data;
    }
}
