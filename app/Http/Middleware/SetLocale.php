<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied per language group in routes/web.php as "locale:<code>". The language
 * comes from the URL prefix only — never from the browser's Accept-Language —
 * so a page always shows the same language to people and crawlers alike.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        app()->setLocale($locale);
        Carbon::setLocale(Locales::setting($locale, 'carbon'));

        return $next($request);
    }
}
