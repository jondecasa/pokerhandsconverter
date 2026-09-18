<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBlogApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('pokerhandsconverter.blog_api.token');

        // No token configured = the API doesn't exist.
        abort_if($expected === '', 404);

        abort_unless(hash_equals($expected, (string) $request->bearerToken()), 401, 'Invalid or missing API token.');

        return $next($request);
    }
}
