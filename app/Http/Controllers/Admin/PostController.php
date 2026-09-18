<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $locale = (string) $request->query('locale');
        $locale = in_array($locale, Locales::codes(), true) ? $locale : '';

        $posts = Post::query()
            ->when($locale !== '', fn (Builder $query) => $query->where('locale', $locale))
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(fn (Builder $query) => $query
                    ->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('excerpt', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->get();

        return view('admin.posts.index', [
            'posts' => $posts,
            'search' => $search,
            'locale' => $locale,
            'total' => Post::count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.create', [
            'post' => new Post,
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = Post::create($request->postData());

        return redirect()->route('admin.posts.edit', $post)
            ->with('status', "Post \"{$post->title}\" created.");
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', [
            'post' => $post,
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $post->update($request->postData());

        return redirect()->route('admin.posts.edit', $post)
            ->with('status', "Post \"{$post->title}\" updated.");
    }

    public function destroy(Post $post): RedirectResponse
    {
        $title = $post->title;
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('status', "Post \"{$title}\" deleted.");
    }
}
