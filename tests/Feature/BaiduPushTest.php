<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Support\BaiduPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BaiduPushTest extends TestCase
{
    use RefreshDatabase;

    private function configure(?string $site = null): void
    {
        config(['pokerhandsconverter.baidu.token' => 'tok123', 'pokerhandsconverter.baidu.site' => $site]);
        $this->app->detectEnvironment(fn () => 'production');
    }

    private function enable(?string $site = null): void
    {
        $this->configure($site);
        Http::fake(['data.zz.baidu.com/*' => Http::response(['remain' => 4999, 'success' => 1, 'not_same_site' => [], 'not_valid' => []])]);
    }

    private function baiduRequests(): Collection
    {
        return Http::recorded()->filter(fn (array $pair) => str_contains($pair[0]->url(), 'data.zz.baidu.com'))->map(fn (array $pair) => $pair[0]);
    }

    #[Test]
    public function urls_are_pushed_as_a_newline_separated_plain_text_body_with_the_site_and_token(): void
    {
        $this->enable();

        $result = app(BaiduPush::class)->submit(['http://localhost/zh-hant', 'http://localhost/zh-hant/pricing', 'http://localhost/zh-hant']);

        $this->assertSame(['success' => 1, 'remain' => 4999], $result);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'http://data.zz.baidu.com/urls?')
            && str_contains($request->url(), 'site='.rawurlencode('http://localhost').'&token=tok123')
            && $request->body() === "http://localhost/zh-hant\nhttp://localhost/zh-hant/pricing"
            && str_contains($request->header('Content-Type')[0], 'text/plain'));
    }

    #[Test]
    public function the_site_can_be_overridden_to_match_what_is_registered_in_baidus_panel(): void
    {
        $this->enable('https://pokerhandsconverter.com');

        app(BaiduPush::class)->submit(['https://pokerhandsconverter.com/zh-hant']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'site='.rawurlencode('https://pokerhandsconverter.com')));
    }

    #[Test]
    public function it_is_off_outside_production_or_without_a_token(): void
    {
        Http::fake();
        config(['pokerhandsconverter.baidu.token' => 'tok123']);
        $this->assertNull(app(BaiduPush::class)->submit(['http://localhost/zh-hant']));

        $this->app->detectEnvironment(fn () => 'production');
        config(['pokerhandsconverter.baidu.token' => null]);
        $this->assertNull(app(BaiduPush::class)->submit(['http://localhost/zh-hant']));

        Http::assertNothingSent();
    }

    #[Test]
    public function a_rejected_push_or_a_network_failure_returns_null_instead_of_throwing(): void
    {
        $this->configure();

        Http::fake(['data.zz.baidu.com/*' => Http::response(['error' => 401, 'message' => 'token is not valid'], 400)]);
        $this->assertNull(app(BaiduPush::class)->submit(['http://localhost/zh-hant']));

        Http::swap(app(Factory::class)->fake(fn () => throw new ConnectionException('down')));
        $this->assertNull(app(BaiduPush::class)->submit(['http://localhost/zh-hant']));
    }

    #[Test]
    public function only_chinese_posts_are_pushed_to_baidu(): void
    {
        $this->enable();

        Post::factory()->published()->create(['slug' => 'english-post']);
        $this->assertCount(0, $this->baiduRequests());

        $zh = Post::factory()->published()->create(['slug' => 'zh-post', 'locale' => 'zh-Hant']);

        $this->assertCount(1, $this->baiduRequests());
        $this->assertSame(route('zh-hant.blog.show', 'zh-post')."\n".route('zh-hant.blog.index'), $this->baiduRequests()->first()->body());
        $this->assertSame(route('zh-hant.blog.show', 'zh-post'), $zh->url());
    }

    #[Test]
    public function the_submit_command_pushes_the_chinese_urls_and_all_urls_with_the_all_flag(): void
    {
        $this->configure();
        Http::fake(['data.zz.baidu.com/*' => Http::response(['remain' => 10, 'success' => 5])]);
        Post::factory()->published()->create(['slug' => 'en-one']);
        Post::factory()->published()->create(['slug' => 'zh-one', 'locale' => 'zh-Hant']);

        $this->artisan('baidu:submit')->expectsOutputToContain('accepted by Baidu (10 left')->assertSuccessful();
        $body = $this->baiduRequests()->last()->body();
        $this->assertStringContainsString(route('zh-hant.home'), $body);
        $this->assertStringContainsString(route('zh-hant.blog.show', 'zh-one'), $body);
        $this->assertStringNotContainsString(route('blog.show', 'en-one'), $body);

        $this->artisan('baidu:submit', ['--all' => true])->assertSuccessful();
        $this->assertStringContainsString(route('blog.show', 'en-one'), $this->baiduRequests()->last()->body());
    }

    #[Test]
    public function the_submit_command_explains_when_baidu_is_off(): void
    {
        Http::fake();

        $this->artisan('baidu:submit')->expectsOutputToContain('Baidu push is off')->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_verification_meta_tag_is_only_rendered_when_configured(): void
    {
        $this->get('/')->assertOk()->assertDontSee('baidu-site-verification', false);

        config(['pokerhandsconverter.baidu.verification' => 'codeva-AbC123']);

        $this->get('/zh-hant')->assertSee('<meta name="baidu-site-verification" content="codeva-AbC123">', false);
    }
}
