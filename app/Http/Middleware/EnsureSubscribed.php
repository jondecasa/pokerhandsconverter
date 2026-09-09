<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to the converter unless the authenticated user has an active
 * (or trialing / grace-period) subscription.
 */
class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $name = config('pokercoinverter.subscription_name', 'default');

        if (! $user || ! $user->subscribed($name)) {
            return redirect()
                ->route('subscription.plans')
                ->with('status', 'You need an active subscription to use the converter.');
        }

        return $next($request);
    }
}
