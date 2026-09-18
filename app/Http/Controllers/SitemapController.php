<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

/**
 * Sitemap of the public marketing pages plus every published blog post —
 * anything behind auth (dashboard, converter, ranges, admin) isn't meant
 * to be indexed.
 *
 * The XML is built here rather than in a Blade view on purpose: Blade
 * tokenizes templates with PHP's own tokenizer, so on servers with
 * short_open_tag enabled a literal XML declaration in a view is parsed as
 * PHP and the page fatals.
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
                'lastmod' => $post->updated_at->toAtomString(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n        <loc>".$this->escape($url['loc'])."</loc>\n";

            if (isset($url['lastmod'])) {
                $xml .= '        <lastmod>'.$this->escape($url['lastmod'])."</lastmod>\n";
            }

            $xml .= "        <changefreq>{$url['changefreq']}</changefreq>\n"
                ."        <priority>{$url['priority']}</priority>\n    </url>\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml']);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
