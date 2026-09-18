<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'testkey1234567890';

    private function enable(): void
    {
        config(['pokerhandsconverter.indexnow.key' => self::KEY]);
        $this->app->detectEnvironment(fn () => 'production');
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
    }

    private function pinged(string $url): \Closure
    {
        return fn (Request $request) => $request->url() === 'https://api.indexnow.org/indexnow'
            && in_array($url, $request['urlList'], true);
    }

    #[Test]
    public function the_key_file_is_served_as_plain_text_only_for_the_configured_key(): void
    {
        config(['pokerhandsconverter.indexnow.key' => self::KEY]);

        $this->get('/'.self::KEY.'.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee(self::KEY);

        $this->get('/someotherkey123456.txt')->assertNotFound();

        config(['pokerhandsconverter.indexnow.key' => null]);
        $this->get('/'.self::KEY.'.txt')->assertNotFound();
    }

    #[Test]
    public function publishing_a_post_notifies_indexnow_with_the_post_and_the_blog_index(): void
    {
        $this->enable();

        $post = Post::factory()->published()->create(['slug' => 'live-post']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['host'] === parse_url(route('home'), PHP_URL_HOST)
            && $request['key'] === self::KEY
            && $request['keyLocation'] === route('indexnow.key', ['key' => self::KEY])
            && $request['urlList'] === [route('blog.show', $post), route('blog.index')]);
    }

    #[Test]
    public function drafts_do_not_notify_anyone(): void
    {
        $this->enable();

        $draft = Post::factory()->create();
        $draft->update(['title' => 'Still a draft']);
        $draft->delete();

        Http::assertNothingSent();
    }

    #[Test]
    public function editing_unpublishing_renaming_and_deleting_a_live_post_all_notify(): void
    {
        $this->enable();
        $post = Post::factory()->published()->create(['slug' => 'first-slug']);
        Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

        $post->update(['title' => 'Edited']);
        Http::assertSent($this->pinged(route('blog.show', $post)));

        $post->update(['slug' => 'second-slug']);
        Http::assertSent($this->pinged(route('blog.show', 'first-slug')));
        Http::assertSent($this->pinged(route('blog.show', 'second-slug')));

        $post->update(['is_published' => false]);
        Http::assertSentCount(3);

        $live = Post::factory()->published()->create();
        $live->delete();
        Http::assertSent($this->pinged(route('blog.show', $live)));
    }

    #[Test]
    public function nothing_is_sent_outside_production_or_without_a_key(): void
    {
        Http::fake();
        config(['pokerhandsconverter.indexnow.key' => self::KEY]);
        Post::factory()->published()->create();

        Http::assertNothingSent();

        $this->app->detectEnvironment(fn () => 'production');
        config(['pokerhandsconverter.indexnow.key' => null]);
        Post::factory()->published()->create();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_failing_indexnow_endpoint_never_breaks_saving_a_post(): void
    {
        $this->enable();

        Http::fake(['api.indexnow.org/*' => Http::response('boom', 500)]);
        $first = Post::factory()->published()->create();

        Http::fake(fn () => throw new ConnectionException('network down'));
        $second = Post::factory()->published()->create();

        $this->assertDatabaseHas('posts', ['id' => $first->id]);
        $this->assertDatabaseHas('posts', ['id' => $second->id]);
    }

    #[Test]
    public function the_submit_command_sends_every_public_url_once_the_key_file_is_reachable(): void
    {
        $this->enable();
        $post = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        Http::fake([
            'api.indexnow.org/*' => Http::response('', 200),
            '*' => Http::response(self::KEY),
        ]);

        $this->artisan('indexnow:submit')->expectsOutputToContain('URLs submitted')->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.indexnow.org/indexnow'
            && in_array(route('home'), $request['urlList'], true)
            && in_array(route('blog.index'), $request['urlList'], true)
            && in_array(route('blog.show', $post), $request['urlList'], true)
            && ! in_array(route('blog.show', $draft), $request['urlList'], true));
    }

    #[Test]
    public function the_submit_command_refuses_to_run_when_the_key_file_is_not_served(): void
    {
        $this->enable();
        Http::fake(['*' => Http::response('<html>not the key</html>')]);

        $this->artisan('indexnow:submit')->expectsOutputToContain("isn't being served")->assertFailed();

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'api.indexnow.org'));
    }

    #[Test]
    public function the_submit_command_explains_when_indexnow_is_off(): void
    {
        Http::fake();

        $this->artisan('indexnow:submit')->expectsOutputToContain('IndexNow is off')->assertFailed();

        Http::assertNothingSent();
    }
}
