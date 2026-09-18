<?php

namespace App\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class PushBlogPosts extends Command
{
    protected $signature = 'blog:push
        {path : A Markdown file, or a directory of .md files}
        {--url= : Site to publish to (defaults to BLOG_API_URL)}
        {--dry-run : Parse the files and show what would be sent, without sending}';

    protected $description = 'Publish Markdown posts (YAML front matter + body) to a site through its /api/posts endpoint';

    public function handle(): int
    {
        $files = $this->resolveFiles((string) $this->argument('path'));

        if ($files === []) {
            $this->error('No Markdown files found at: '.$this->argument('path'));

            return self::FAILURE;
        }

        $posts = [];
        foreach ($files as $file) {
            try {
                $posts[$file] = $this->parse($file);
            } catch (RuntimeException|ParseException $e) {
                $this->error(basename($file).': '.$e->getMessage());

                return self::FAILURE;
            }
        }

        if ($this->option('dry-run')) {
            foreach ($posts as $file => $payload) {
                $state = isset($payload['is_published']) ? ($payload['is_published'] ? 'publish' : 'draft') : 'unchanged';
                $this->line(sprintf('%s -> %s [%s, %d chars]', basename($file), $payload['slug'], $state, strlen($payload['body'])));
            }

            return self::SUCCESS;
        }

        $url = rtrim((string) ($this->option('url') ?: config('pokerhandsconverter.blog_api.url')), '/');
        $token = (string) config('pokerhandsconverter.blog_api.token');

        if ($url === '' || $token === '') {
            $this->error('Set BLOG_API_URL (or pass --url) and BLOG_API_TOKEN in .env.');

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($posts as $file => $payload) {
            $failed += $this->send($url, $token, basename($file), $payload) ? 0 : 1;
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, mixed> $payload */
    private function send(string $url, string $token, string $label, array $payload): bool
    {
        try {
            $response = Http::withToken($token)->acceptJson()->timeout(30)->post("{$url}/api/posts", $payload);
        } catch (ConnectionException $e) {
            $this->error("{$label}: could not reach {$url} ({$e->getMessage()})");

            return false;
        }

        if ($response->successful()) {
            $this->info(sprintf(
                '%s  %s  [%s]  %s',
                $response->json('created') ? 'created' : 'updated',
                $payload['slug'],
                $response->json('data.status'),
                $response->json('data.url'),
            ));

            return true;
        }

        $this->error("{$label}: HTTP {$response->status()} — ".match (true) {
            $response->status() === 401 => 'token rejected (BLOG_API_TOKEN must match the site\'s .env).',
            $response->status() === 404 => 'API not available there (not deployed yet, or BLOG_API_TOKEN unset on the site).',
            $response->status() === 403 => 'blocked before reaching Laravel — likely a firewall/WAF rule.',
            $response->status() === 422 => 'validation failed.',
            default => Str::limit(trim(strip_tags($response->body())), 200),
        });

        foreach ($response->json('errors', []) as $field => $messages) {
            $this->line("  {$field}: ".implode(' ', $messages));
        }

        return false;
    }

    /** @return list<string> */
    private function resolveFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (is_dir($path)) {
            $files = glob(rtrim($path, '/\\').'/*.md') ?: [];
            sort($files);

            return $files;
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $file): array
    {
        $raw = ltrim((string) file_get_contents($file), "\xEF\xBB\xBF");

        if (! preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', $raw, $m)) {
            throw new RuntimeException('missing front matter (a "---" block at the top of the file).');
        }

        $meta = Yaml::parse($m[1], Yaml::PARSE_DATETIME);
        $body = trim($m[2]);

        if (! is_array($meta) || blank($meta['title'] ?? null)) {
            throw new RuntimeException('front matter needs a title.');
        }

        if ($body === '') {
            throw new RuntimeException('the post body is empty.');
        }

        $payload = [
            'slug' => (string) ($meta['slug'] ?? Str::slug(pathinfo($file, PATHINFO_FILENAME))),
            'title' => (string) $meta['title'],
            'body' => $body,
        ];

        foreach (['locale', 'translation_of', 'excerpt', 'meta_title', 'meta_description'] as $key) {
            if (isset($meta[$key])) {
                $payload[$key] = (string) $meta[$key];
            }
        }

        if (array_key_exists('published', $meta)) {
            $payload['is_published'] = (bool) $meta['published'];
        }

        if (isset($meta['published_at'])) {
            $payload['published_at'] = $meta['published_at'] instanceof DateTimeInterface
                ? $meta['published_at']->format(DATE_ATOM)
                : (string) $meta['published_at'];
        }

        return $payload;
    }
}
