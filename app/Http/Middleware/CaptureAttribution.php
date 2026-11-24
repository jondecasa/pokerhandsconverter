<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers, in the session, where a visitor first came from: the external site that linked to us, any utm_*
 * parameters, and the page they landed on. If they register in the same visit it is saved on their account, so
 * the owner can tell which posts and links bring subscribers. Nothing leaves the site and no extra cookie is set.
 */
class CaptureAttribution
{
    public const KEY = 'attribution';

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCapture($request)) {
            $request->session()->put(self::KEY, $this->attribution($request));
        }

        return $next($request);
    }

    /** First touch only, on a real page view by a visitor who is not signed in. */
    private function shouldCapture(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->expectsJson()
            && ! $request->ajax()
            && $request->hasSession()
            && ! $request->session()->has(self::KEY)
            && ! $request->user()
            && ! $request->is('up', 'build/*', 'sitemap.xml', 'robots.txt', '*.txt');
    }

    /** @return array<string, string> */
    private function attribution(Request $request): array
    {
        $data = array_filter([
            'referrer' => $this->referrerHost($request),
            'utm_source' => $this->clean($request->query('utm_source')),
            'utm_medium' => $this->clean($request->query('utm_medium')),
            'utm_campaign' => $this->clean($request->query('utm_campaign')),
            'landing' => '/'.ltrim(mb_substr($request->path(), 0, 120), '/'),
        ]);

        return $data;
    }

    /** Only the host of an outside referrer, never its path or query string. */
    private function referrerHost(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer');
        $host = strtolower((string) parse_url($referer, PHP_URL_HOST));

        if ($host === '') {
            return null;
        }

        $own = strtolower($request->getHost());

        if ($host === $own || preg_replace('/^www\./', '', $host) === preg_replace('/^www\./', '', $own)) {
            return null;
        }

        return mb_substr(preg_replace('/^www\./', '', $host), 0, 120);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $value = trim(preg_replace('/[^A-Za-z0-9._ \-]/', '', $value) ?? '');

        return $value === '' ? null : mb_substr($value, 0, 80);
    }
}
