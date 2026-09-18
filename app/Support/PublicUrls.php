<?php

namespace App\Support;

use App\Models\Post;

class PublicUrls
{
    /**
     * Every public, indexable URL: the marketing pages plus each published
     * blog post. Anything behind auth (dashboard, converter, ranges, admin)
     * is deliberately left out. Used by the sitemap and by IndexNow.
     *
     * @return list<array{loc: string, priority: string, changefreq: string, lastmod?: string}>
     */
    public static function all(): array
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
                'lastmod' => $post->updated_at->toAtomString(),
            ];
        }

        return $urls;
    }
}
