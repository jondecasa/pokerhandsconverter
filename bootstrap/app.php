<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBlogApiToken;
use App\Http\Middleware\EnsureSubscribed;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
