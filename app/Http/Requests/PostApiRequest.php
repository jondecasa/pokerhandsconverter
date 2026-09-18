<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** The language the post is in; a post is identified by (locale, slug). */
    public function locale(): string
    {
        return $this->input('locale', Locales::default());
    }

    public function rules(): array
    {
        // Creating needs the content; updating may send only what changes.
        $exists = Post::where('slug', $this->input('slug'))->where('locale', $this->locale())->exists();
        $required = $exists ? ['sometimes', 'required'] : ['required'];

        return [
            'slug' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'locale' => ['sometimes', Rule::in(Locales::codes())],
            'translation_of' => ['nullable', 'string', 'max:150'],
            'title' => [...$required, 'string', 'max:150'],
            'body' => [...$required, 'string'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Only the fields the client actually sent, so an update never blanks
     * something it didn't mention.
     *
     * @return array<string, mixed>
     */
    public function postAttributes(Post $post): array
    {
        $data = $this->safe()->only(['slug', 'locale', 'translation_of', 'title', 'body', 'excerpt', 'meta_title', 'meta_description']);
        $data['locale'] = $this->locale();

        $isPublished = $this->has('is_published') ? $this->boolean('is_published') : $post->is_published;
        if ($this->has('is_published')) {
            $data['is_published'] = $isPublished;
        }

        if ($this->filled('published_at')) {
            $data['published_at'] = $this->date('published_at');
        } elseif ($isPublished && ! $post->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
