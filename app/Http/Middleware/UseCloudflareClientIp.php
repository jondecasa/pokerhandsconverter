<?php

namespace App\Http\Middleware;

use App\Support\CloudflareIps;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Behind Cloudflare and then a reverse proxy (Caddy), the proxy only sees a Cloudflare address, so it writes that
 * into X-Forwarded-For and the visitor's own address is left in Cloudflare's CF-Connecting-IP header. Rate limits
 * and logs need the visitor's, so take it from there — but only when the request really came through Cloudflare,
 * which a direct hit on the server cannot fake: the proxy fills X-Forwarded-For with whoever really connected.
 */
class UseCloudflareClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $visitor = (string) $request->headers->get('CF-Connecting-IP');

        if (filter_var($visitor, FILTER_VALIDATE_IP) && $this->cameThroughCloudflare($request)) {
            // the connecting address is now the visitor's, so it is not a trusted proxy and X-Forwarded-For is ignored
            $request->server->set('REMOTE_ADDR', $visitor);
        }

        return $next($request);
    }

    private function cameThroughCloudflare(Request $request): bool
    {
        $ranges = CloudflareIps::ranges();

        if (IpUtils::checkIp((string) $request->server->get('REMOTE_ADDR'), $ranges)) {
            return true;
        }

        $forwarded = array_map('trim', explode(',', (string) $request->headers->get('X-Forwarded-For')));
        $nearest = (string) end($forwarded);

        return $nearest !== '' && IpUtils::checkIp($nearest, $ranges);
    }
}
