<?php

namespace App\Support;

use App\Models\Post;

class PublicUrls
{
    /**
     * Every public, indexable URL in every language: the marketing pages plus
     * each published blog post. Anything behind auth (dashboard, converter,
     * ranges, admin) is deliberately left out. Used by the sitemap, IndexNow and
     * Baidu.
     *
     * `alternates` (locale => URL, itself included) is only present when the
     * page exists in more than one language.
     *
     * @return list<array{loc: string, locale: string, priority: string, changefreq: string, lastmod?: string, alternates?: array<string, string>}>
     */
    public static function all(): array
    {
        $urls = [];

        // Pages that exist in every language, and blog indexes where a language has posts.
        $pages = [
            'home' => ['1.0', 'weekly'],
            'pricing' => ['0.9', 'weekly'],
            'blog.index' => ['0.7', 'weekly'],
            'contact' => ['0.5', 'monthly'],
        ];

        foreach ($pages as $name => [$priority, $changefreq]) {
            $versions = [];
            foreach (Locales::codes() as $locale) {
                if (Locales::pageAvailable($name, $locale)) {
                    $versions[$locale] = route(Locales::routeName($name, $locale));
                }
            }

            foreach ($versions as $locale => $loc) {
                $urls[] = array_filter([
                    'loc' => $loc,
                    'locale' => $locale,
                    'priority' => $priority,
                    'changefreq' => $changefreq,
                    'alternates' => count($versions) > 1 ? $versions : null,
                ]);
            }
        }

        // Legal pages only exist in the default language.
        foreach ([['terms', '0.3', 'yearly'], ['privacy', '0.3', 'yearly']] as [$name, $priority, $changefreq]) {
            $urls[] = ['loc' => route($name), 'locale' => Locales::default(), 'priority' => $priority, 'changefreq' => $changefreq];
        }

        $posts = Post::published()->latestFirst()->get();

        // Group every post with its translations in one pass (no query per post).
        $groups = [];
        foreach ($posts as $post) {
            $key = Locales::isDefault($post->locale) ? $post->slug : $post->translation_of;
            if ($key) {
                $groups[$key][$post->locale] = $post->url();
            }
        }

        foreach ($posts as $post) {
            $key = Locales::isDefault($post->locale) ? $post->slug : $post->translation_of;
            $alternates = $key ? ($groups[$key] ?? []) : [];

            $urls[] = array_filter([
                'loc' => $post->url(),
                'locale' => $post->locale,
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $post->updated_at->toAtomString(),
                'alternates' => count($alternates) > 1 ? $alternates : null,
            ]);
        }

        return $urls;
    }
}
