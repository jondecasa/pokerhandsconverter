<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PushBlogPostsCommandTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/blogpush-'.uniqid();
        mkdir($this->dir);
        config(['pokerhandsconverter.blog_api.token' => 'tok', 'pokerhandsconverter.blog_api.url' => null]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function markdownPost(string $name, string $frontMatter, string $body = "## Hello\n\nWorld."): string
    {
        $path = "{$this->dir}/{$name}";
        file_put_contents($path, "---\n{$frontMatter}\n---\n\n{$body}\n");

        return $path;
    }

    private function fakeSuccess(): void
    {
        Http::fake(['*' => Http::response([
            'created' => true,
            'data' => ['status' => 'published', 'url' => 'https://site.test/blog/my-post'],
        ], 201)]);
    }

    #[Test]
    public function it_sends_the_parsed_front_matter_and_body_with_the_token(): void
    {
        $this->fakeSuccess();
        $file = $this->markdownPost('my-post.md', <<<'YAML'
title: "CoinPoker vs PokerStars: What's Different"
slug: coinpoker-vs-pokerstars
excerpt: A short summary.
meta_description: Meta text.
published: true
published_at: 2026-10-01 09:30:00
YAML);

        $this->artisan('blog:push', ['path' => $file, '--url' => 'https://site.test/'])
            ->expectsOutputToContain('created  coinpoker-vs-pokerstars  [published]  https://site.test/blog/my-post')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://site.test/api/posts'
            && $request->hasHeader('Authorization', 'Bearer tok')
            && $request['slug'] === 'coinpoker-vs-pokerstars'
            && $request['title'] === "CoinPoker vs PokerStars: What's Different"
            && $request['excerpt'] === 'A short summary.'
            && $request['meta_description'] === 'Meta text.'
            && $request['is_published'] === true
            && str_starts_with($request['published_at'], '2026-10-01T09:30:00')
            && $request['body'] === "## Hello\n\nWorld.");
    }

    #[Test]
    public function the_slug_defaults_to_the_file_name_and_publishing_is_left_alone_when_unspecified(): void
    {
        $this->fakeSuccess();
        $file = $this->markdownPost('from-the-filename.md', 'title: Some title');

        $this->artisan('blog:push', ['path' => $file, '--url' => 'https://site.test'])->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request['slug'] === 'from-the-filename'
            && ! isset($request->data()['is_published']));
    }

    #[Test]
    public function a_directory_pushes_every_markdown_file_in_it(): void
    {
        $this->fakeSuccess();
        $this->markdownPost('a.md', 'title: A');
        $this->markdownPost('b.md', 'title: B');
        file_put_contents("{$this->dir}/notes.txt", 'ignored');

        $this->artisan('blog:push', ['path' => $this->dir, '--url' => 'https://site.test'])->assertSuccessful();

        Http::assertSentCount(2);
    }

    #[Test]
    public function a_dry_run_sends_nothing(): void
    {
        Http::fake();
        $file = $this->markdownPost('draft.md', "title: Draft\npublished: false");

        $this->artisan('blog:push', ['path' => $file, '--dry-run' => true])
            ->expectsOutputToContain('draft.md -> draft [draft')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_file_without_a_title_fails_before_anything_is_sent(): void
    {
        Http::fake();
        $file = $this->markdownPost('bad.md', 'slug: bad');

        $this->artisan('blog:push', ['path' => $file, '--url' => 'https://site.test'])
            ->expectsOutputToContain('front matter needs a title')
            ->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_rejected_token_is_reported_and_fails_the_command(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid or missing API token.'], 401)]);
        $file = $this->markdownPost('p.md', 'title: P');

        $this->artisan('blog:push', ['path' => $file, '--url' => 'https://site.test'])
            ->expectsOutputToContain('token rejected')
            ->assertFailed();
    }

    #[Test]
    public function validation_errors_from_the_site_are_listed(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['slug' => ['The slug format is invalid.']]], 422)]);
        $file = $this->markdownPost('p.md', 'title: P');

        $this->artisan('blog:push', ['path' => $file, '--url' => 'https://site.test'])
            ->expectsOutputToContain('slug: The slug format is invalid.')
            ->assertFailed();
    }

    #[Test]
    public function it_refuses_to_run_without_a_url_and_token(): void
    {
        Http::fake();
        config(['pokerhandsconverter.blog_api.token' => null]);
        $file = $this->markdownPost('p.md', 'title: P');

        $this->artisan('blog:push', ['path' => $file])
            ->expectsOutputToContain('Set BLOG_API_URL')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
