<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostApiRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Post::query()->orderByDesc('updated_at')->get()->map(fn (Post $post) => $this->summary($post)),
        ]);
    }

    /**
     * Create-or-update by language + slug. Deliberately POST-only and with no delete:
     * some hosts' WAFs reject PUT/DELETE, and a post is retired by sending
     * is_published=false rather than being destroyed remotely.
     */
    public function upsert(PostApiRequest $request): JsonResponse
    {
        $post = Post::firstOrNew(['locale' => $request->locale(), 'slug' => $request->validated('slug')]);
        $created = ! $post->exists;

        $post->fill($request->postAttributes($post))->save();

        return response()->json(['data' => $this->summary($post), 'created' => $created], $created ? 201 : 200);
    }

    /** @return array<string, mixed> */
    private function summary(Post $post): array
    {
        return [
            'slug' => $post->slug,
            'locale' => $post->locale,
            'title' => $post->title,
            'status' => $post->isPublished() ? 'published' : ($post->is_published ? 'scheduled' : 'draft'),
            'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'url' => $post->url(),
        ];
    }
}
