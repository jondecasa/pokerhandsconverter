<?php

namespace App\Http\Controllers;

use App\Support\PublicUrls;
use Illuminate\Http\Response;

/**
 * Sitemap of the public marketing pages plus every published blog post
 * (see PublicUrls).
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
        $urls = PublicUrls::all();

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
