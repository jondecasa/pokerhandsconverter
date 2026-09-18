<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pushes new URLs to Baidu's webmaster platform (the "active push" API of
 * ziyuan.baidu.com), the closest thing Baidu has to IndexNow. Needs the site to
 * be added and verified there first, and its API token. Baidu enforces a daily
 * quota per site, so callers should only send URLs that are worth indexing
 * there (the Chinese-language ones). Never throws.
 */
class BaiduPush
{
    private const ENDPOINT = 'http://data.zz.baidu.com/urls';

    public function token(): string
    {
        return (string) config('pokerhandsconverter.baidu.token');
    }

    public function enabled(): bool
    {
        return app()->isProduction() && $this->token() !== '';
    }

    /** The site exactly as registered in Baidu's panel (defaults to scheme + host of the home page). */
    public function site(): string
    {
        $configured = (string) config('pokerhandsconverter.baidu.site');

        if ($configured !== '') {
            return $configured;
        }

        $home = parse_url(route('home'));

        return $home['scheme'].'://'.$home['host'];
    }

    /**
     * @param  list<string>  $urls
     * @return array{success: int, remain: ?int}|null null when nothing was accepted
     */
    public function submit(array $urls): ?array
    {
        $urls = array_values(array_unique(array_filter($urls)));

        if (! $this->enabled() || $urls === []) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->withBody(implode("\n", $urls), 'text/plain')
                ->post(self::ENDPOINT.'?'.http_build_query(['site' => $this->site(), 'token' => $this->token()]));
        } catch (Throwable $e) {
            Log::warning('Baidu push failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || $response->json('error') !== null) {
            Log::warning('Baidu rejected the push', ['status' => $response->status(), 'body' => Str::limit($response->body(), 200)]);

            return null;
        }

        foreach (['not_same_site', 'not_valid'] as $problem) {
            if (filled($response->json($problem))) {
                Log::warning("Baidu push: {$problem}", ['urls' => $response->json($problem)]);
            }
        }

        return ['success' => (int) $response->json('success', 0), 'remain' => $response->json('remain')];
    }
}
