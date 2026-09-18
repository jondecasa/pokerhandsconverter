<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class PostApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Creating needs the content; updating may send only what changes.
        $required = Post::where('slug', $this->input('slug'))->exists() ? ['sometimes', 'required'] : ['required'];

        return [
            'slug' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
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
        $data = $this->safe()->only(['slug', 'title', 'body', 'excerpt', 'meta_title', 'meta_description']);

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
