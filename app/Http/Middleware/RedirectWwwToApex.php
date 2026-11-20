<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The site lives at the bare domain; www.<domain> would otherwise serve a second, identical copy with its own
 * canonical URL. Send it to the bare domain with a permanent redirect, keeping the path and the query string.
 */
class RedirectWwwToApex
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (! str_starts_with($host, 'www.') || substr_count($host, '.') < 2) {
            return $next($request);
        }

        $target = $request->getScheme().'://'.substr($host, 4).$request->getRequestUri();

        // 301 turns a POST into a GET; 308 keeps the method for anything that is not a plain page view
        return redirect()->away($target, $request->isMethodSafe() ? 301 : 308);
    }
}
