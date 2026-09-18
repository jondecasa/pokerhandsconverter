<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

/**
 * Sitemap of the public marketing pages plus every published blog post —
 * anything behind auth (dashboard, converter, ranges, admin) isn't meant
 * to be indexed.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('pricing'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('blog.index'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach (Post::published()->latestFirst()->get() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at,
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
