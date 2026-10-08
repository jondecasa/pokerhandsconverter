<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBlogApiToken;
use App\Http\Middleware\EnsureSubscribed;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Support\CloudflareIps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Cloudflare the connecting address is Cloudflare's; the visitor's
        // is in X-Forwarded-For. Rate limits and logs need the visitor's.
        $middleware->trustProxies(at: CloudflareIps::ranges(), headers: Request::HEADER_X_FORWARDED_FOR);

        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'subscribed' => EnsureSubscribed::class,
            'admin' => EnsureAdmin::class,
            'blog.api' => EnsureBlogApiToken::class,
            'locale' => SetLocale::class,
        ]);

        // The language must be set before route model binding runs (posts are
        // looked up per language).
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
