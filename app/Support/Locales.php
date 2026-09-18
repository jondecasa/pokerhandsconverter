<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The languages of the public site: URL prefixes, route names per language and
 * the alternate-language links search engines use (hreflang).
 */
class Locales
{
    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return config('pokerhandsconverter.locales');
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        return config('pokerhandsconverter.default_locale');
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function isDefault(?string $locale = null): bool
    {
        return ($locale ?? self::current()) === self::default();
    }

    public static function setting(string $locale, string $key): string
    {
        return self::all()[$locale][$key] ?? self::all()[self::default()][$key];
    }

    public static function prefix(string $locale): string
    {
        return self::setting($locale, 'prefix');
    }

    /**
     * The route name of a page in a language: "pricing" stays as it is in the
     * default language, becomes "zh-hant.pricing" in the others.
     */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $prefix = self::prefix($locale ?? self::current());

        return $prefix === '' ? $name : $prefix.'.'.$name;
    }

    /** The name without any language prefix ("zh-hant.pricing" -> "pricing"). */
    public static function baseRouteName(?string $name): ?string
    {
        foreach (self::all() as $settings) {
            if ($settings['prefix'] !== '' && Str::startsWith((string) $name, $settings['prefix'].'.')) {
                return Str::after($name, $settings['prefix'].'.');
            }
        }

        return $name;
    }

    /**
     * URL of a page in a language (the current one by default). A page that has
     * no version in that language (terms, login...) links to the default one.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $localized = self::routeName($name, $locale);

        return route(Route::has($localized) ? $localized : $name, $parameters);
    }

    /** Whether a localized page has anything to show in that language. */
    public static function pageAvailable(string $baseName, string $locale): bool
    {
        if ($baseName === 'blog.index') {
            return Post::published()->where('locale', $locale)->exists();
        }

        return in_array($baseName, config('pokerhandsconverter.localized_pages'), true);
    }

    /**
     * The versions of the current page in every language, for hreflang and the
     * language switcher. Empty when the page only exists in one language.
     *
     * @return array<string, string> locale => URL
     */
    public static function alternates(): array
    {
        $base = self::baseRouteName(request()->route()?->getName());

        if (! in_array($base, config('pokerhandsconverter.localized_pages'), true)) {
            return [];
        }

        $alternates = [];
        foreach (self::codes() as $locale) {
            if (self::pageAvailable($base, $locale)) {
                $alternates[$locale] = route(self::routeName($base, $locale));
            }
        }

        return count($alternates) > 1 ? $alternates : [];
    }
}
