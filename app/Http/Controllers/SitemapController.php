<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Static sitemap of the public marketing pages only — anything behind
 * auth (dashboard, converter, ranges, admin) isn't meant to be indexed.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['route' => 'contact', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['route' => 'terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
