<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => Post::published()->latestFirst()->paginate(10),
        ]);
    }

    public function show(Request $request, Post $post): View
    {
        abort_unless($post->isPublished() || $request->user()?->isAdmin(), 404);

        return view('blog.show', [
            'post' => $post,
            'related' => Post::published()
                ->whereKeyNot($post->id)
                ->latestFirst()
                ->limit(3)
                ->get(),
        ]);
    }
}
