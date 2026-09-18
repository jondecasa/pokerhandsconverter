<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Tells IndexNow search engines (Bing, Yandex, Naver, Seznam, Yep...) that URLs
 * were added, updated or removed, instead of waiting for them to be crawled.
 * Only active in production with a key configured, so local and test runs never
 * ping anything.
 */
class IndexNow
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    public function key(): string
    {
        return (string) config('pokerhandsconverter.indexnow.key');
    }

    public function enabled(): bool
    {
        return app()->isProduction() && $this->key() !== '';
    }

    public function keyUrl(): string
    {
        return route('indexnow.key', ['key' => $this->key()]);
    }

    /**
     * Never throws: a failed ping must not break saving a post.
     *
     * @param  list<string>  $urls
     */
    public function submit(array $urls): bool
    {
        $urls = array_values(array_unique(array_filter($urls)));

        if (! $this->enabled() || $urls === []) {
            return false;
        }

        try {
            $response = Http::timeout(5)->acceptJson()->post(self::ENDPOINT, [
                'host' => parse_url(route('home'), PHP_URL_HOST),
                'key' => $this->key(),
                'keyLocation' => $this->keyUrl(),
                'urlList' => $urls,
            ]);
        } catch (Throwable $e) {
            Log::warning('IndexNow submission failed', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('IndexNow rejected the submission', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 200),
            ]);
        }

        return $response->successful();
    }
}
