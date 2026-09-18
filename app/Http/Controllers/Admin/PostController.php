<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => Post::query()->orderByDesc('created_at')->get(),
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
