<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use App\Support\PublicUrls;
use Illuminate\Http\Response;

/**
 * Sitemap of the public marketing pages plus every published blog post, in
 * every language (see PublicUrls), with hreflang alternates between the
 * language versions of a page.
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach (PublicUrls::all() as $url) {
            $xml .= "    <url>\n        <loc>".$this->escape($url['loc'])."</loc>\n";

            if (isset($url['lastmod'])) {
                $xml .= '        <lastmod>'.$this->escape($url['lastmod'])."</lastmod>\n";
            }

            foreach ($url['alternates'] ?? [] as $locale => $href) {
                $xml .= $this->alternate(Locales::setting($locale, 'hreflang'), $href);
            }

            if (isset($url['alternates'][Locales::default()])) {
                $xml .= $this->alternate('x-default', $url['alternates'][Locales::default()]);
            }

            $xml .= "        <changefreq>{$url['changefreq']}</changefreq>\n"
                ."        <priority>{$url['priority']}</priority>\n    </url>\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml']);
    }

    private function alternate(string $hreflang, string $href): string
    {
        return '        <xhtml:link rel="alternate" hreflang="'.$hreflang.'" href="'.$this->escape($href).'"/>'."\n";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
