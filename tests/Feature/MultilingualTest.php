<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Post;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultilingualTest extends TestCase
{
    use RefreshDatabase;

    private function zhPost(array $attributes = []): Post
    {
        return Post::factory()->published()->create($attributes + ['locale' => 'zh-Hant']);
    }

    #[Test]
    public function the_default_language_stays_english_at_the_site_root(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Your CoinPoker hands,')
            ->assertSee('<meta property="og:locale" content="en_US">', false);
    }

    #[Test]
    public function the_traditional_chinese_site_lives_under_its_own_prefix(): void
    {
        $this->get('/zh-hant')
            ->assertOk()
            ->assertSee('<html lang="zh-Hant"', false)
            ->assertSee('追蹤軟體讀得懂')
            ->assertSee('<meta property="og:locale" content="zh_TW">', false)
            ->assertSee('<link rel="canonical" href="'.route('zh-hant.home').'">', false)
            ->assertDontSee('Your CoinPoker hands,');

        $this->get('/zh-hant/pricing')->assertOk()->assertSee('方案價格');
        $this->get('/zh-hant/contact')->assertOk()->assertSee('送出訊息');
    }

    #[Test]
    public function the_language_never_depends_on_the_browsers_accept_language_header(): void
    {
        $this->get('/', ['Accept-Language' => 'zh-TW,zh;q=0.9'])->assertOk()->assertSee('<html lang="en"', false);
    }

    #[Test]
    public function localized_pages_declare_every_language_version_and_an_x_default(): void
    {
        foreach (['/', '/zh-hant', '/pricing', '/zh-hant/pricing', '/contact', '/zh-hant/contact'] as $url) {
            $base = Locales::baseRouteName(app('router')->getRoutes()->match(request()->create($url))->getName());

            $this->get($url)
                ->assertOk()
                ->assertSee('<link rel="alternate" hreflang="en" href="'.route($base).'">', false)
                ->assertSee('<link rel="alternate" hreflang="zh-Hant" href="'.route('zh-hant.'.$base).'">', false)
                ->assertSee('<link rel="alternate" hreflang="x-default" href="'.route($base).'">', false);
        }
    }

    #[Test]
    public function pages_that_only_exist_in_english_declare_no_alternates(): void
    {
        $this->get('/terms')->assertOk()->assertDontSee('rel="alternate"', false);
        $this->get('/privacy')->assertOk()->assertDontSee('rel="alternate"', false);
    }

    #[Test]
    public function the_language_switcher_links_to_the_same_page_in_the_other_language(): void
    {
        $this->get('/pricing')->assertSee('href="'.route('zh-hant.pricing').'"', false)->assertSee('繁體中文');
        $this->get('/zh-hant/pricing')->assertSee('href="'.route('pricing').'"', false)->assertSee('English');

        // A page with no translation falls back to the other language's home.
        $this->get('/terms')->assertSee('href="'.route('zh-hant.home').'"', false);
    }

    #[Test]
    public function plan_names_features_and_prices_are_translated_when_a_translation_exists(): void
    {
        Plan::factory()->create(['name' => 'Monthly', 'price' => 9, 'interval' => 'month', 'features' => ['Cancel anytime, self-serve']]);
        Plan::factory()->create(['name' => 'Whale Plan', 'price' => 0, 'stripe_price_id' => null, 'features' => ['Something we never translated']]);

        $this->get('/zh-hant/pricing')
            ->assertOk()
            ->assertSee('月付')
            ->assertSee('/ 月')
            ->assertSee('隨時可自行取消')
            ->assertSee('免費')
            ->assertSee('Whale Plan')
            ->assertSee('Something we never translated');

        $this->get('/pricing')->assertSee('Monthly')->assertSee('/ month');
    }

    #[Test]
    public function the_chinese_contact_form_answers_in_chinese_and_english_validation_is_untouched(): void
    {
        Mail::fake();

        $this->from('/zh-hant/contact')->post('/zh-hant/contact', ['email' => 'nope'])
            ->assertRedirect('/zh-hant/contact')
            ->assertSessionHasErrors(['name' => '你的姓名 為必填欄位。', 'email' => '電子郵件 必須是有效的電子郵件地址。']);

        $this->from('/zh-hant/contact')->post('/zh-hant/contact', [
            'name' => 'Wang', 'email' => 'wang@example.com', 'message' => 'One hand will not import into PT4.',
        ])->assertSessionHas('status', '謝謝——你的訊息已送出。我們通常會在一到兩天內回覆。');

        $this->from('/contact')->post('/contact', ['email' => 'nope'])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);
    }

    #[Test]
    public function the_blog_only_shows_in_the_chinese_navigation_once_there_are_chinese_posts(): void
    {
        Post::factory()->published()->create(['title' => 'English Only Post']);

        $this->get('/zh-hant')->assertOk()->assertDontSee('href="'.route('zh-hant.blog.index').'"', false);
        $this->get('/pricing')->assertSee('href="'.route('blog.index').'"', false);

        $this->zhPost(['title' => '中文文章', 'slug' => 'zh-only-post']);

        $this->get('/zh-hant')->assertSee('href="'.route('zh-hant.blog.index').'"', false);
        $this->get('/zh-hant/blog')->assertOk()->assertSee('中文文章')->assertDontSee('English Only Post');
        $this->get('/blog')->assertSee('English Only Post')->assertDontSee('中文文章');
    }

    #[Test]
    public function the_same_slug_can_exist_in_both_languages_and_each_url_shows_its_own(): void
    {
        Post::factory()->published()->create(['slug' => 'what-is-rake', 'title' => 'What is rake']);
        $zh = $this->zhPost(['slug' => 'what-is-rake', 'title' => '什麼是抽水', 'translation_of' => 'what-is-rake']);

        $this->get('/blog/what-is-rake')->assertOk()->assertSee('What is rake')->assertDontSee('什麼是抽水');
        $this->get('/zh-hant/blog/what-is-rake')->assertOk()->assertSee('什麼是抽水');
        $this->assertSame(route('zh-hant.blog.show', 'what-is-rake'), $zh->url());
    }

    #[Test]
    public function a_post_that_only_exists_in_english_404s_under_the_chinese_prefix(): void
    {
        Post::factory()->published()->create(['slug' => 'english-only']);

        $this->get('/zh-hant/blog/english-only')->assertNotFound();
    }

    #[Test]
    public function translated_posts_link_to_each_other_with_hreflang_and_untranslated_ones_do_not(): void
    {
        Post::factory()->published()->create(['slug' => 'bankroll', 'title' => 'Bankroll']);
        $this->zhPost(['slug' => 'bankroll', 'title' => '資金管理', 'translation_of' => 'bankroll']);
        Post::factory()->published()->create(['slug' => 'lonely']);

        foreach (['/blog/bankroll', '/zh-hant/blog/bankroll'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<link rel="alternate" hreflang="en" href="'.route('blog.show', 'bankroll').'">', false)
                ->assertSee('<link rel="alternate" hreflang="zh-Hant" href="'.route('zh-hant.blog.show', 'bankroll').'">', false)
                ->assertSee('<link rel="alternate" hreflang="x-default" href="'.route('blog.show', 'bankroll').'">', false);
        }

        $this->get('/blog/lonely')->assertOk()->assertDontSee('rel="alternate"', false);
    }

    #[Test]
    public function a_chinese_post_has_chinese_chrome_and_structured_data_in_its_language(): void
    {
        $this->zhPost(['slug' => 'zh-post', 'title' => '中文標題', 'published_at' => '2026-09-18 12:00:00']);

        $this->get('/zh-hant/blog/zh-post')
            ->assertOk()
            ->assertSee('2026年9月18日')
            ->assertSee('準備好轉換你的牌譜了嗎？')
            ->assertSee('"inLanguage":"zh-Hant"', false)
            ->assertSee('<link rel="canonical" href="'.route('zh-hant.blog.show', 'zh-post').'">', false);
    }

    #[Test]
    public function the_sitemap_lists_every_language_with_hreflang_alternates(): void
    {
        Post::factory()->published()->create(['slug' => 'rake']);
        $this->zhPost(['slug' => 'rake', 'translation_of' => 'rake']);

        $content = $this->get('/sitemap.xml')->assertOk()->getContent();
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        $locs = array_map(fn ($url) => (string) $url->loc, iterator_to_array($xml->url, false));

        foreach ([route('home'), route('zh-hant.home'), route('zh-hant.pricing'), route('zh-hant.contact'), route('blog.show', 'rake'), route('zh-hant.blog.show', 'rake'), route('zh-hant.blog.index')] as $expected) {
            $this->assertContains($expected, $locs);
        }

        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $content);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="zh-Hant" href="'.route('zh-hant.blog.show', 'rake').'"/>', $content);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="x-default" href="'.route('blog.show', 'rake').'"/>', $content);
        // Legal pages exist in English only.
        $this->assertNotContains(route('zh-hant.home').'/terms', $locs);
    }

    #[Test]
    public function the_chinese_blog_index_is_left_out_of_the_sitemap_until_there_are_chinese_posts(): void
    {
        $locs = array_map(fn ($url) => (string) $url->loc, iterator_to_array(simplexml_load_string($this->get('/sitemap.xml')->getContent())->url, false));

        $this->assertNotContains(route('zh-hant.blog.index'), $locs);
        $this->assertContains(route('zh-hant.home'), $locs);
    }

    #[Test]
    public function the_admin_edits_the_right_post_when_two_languages_share_a_slug(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
        $en = Post::factory()->published()->create(['slug' => 'shared', 'title' => 'English title']);
        $zh = $this->zhPost(['slug' => 'shared', 'title' => '中文標題', 'translation_of' => 'shared']);

        $this->actingAs($admin)->get(route('admin.posts.edit', $zh))->assertOk()->assertSee('中文標題');

        $this->actingAs($admin)->put(route('admin.posts.update', $zh), [
            'title' => '新標題', 'slug' => 'shared', 'locale' => 'zh-Hant', 'translation_of' => 'shared',
            'body' => 'Body', 'is_published' => '1', 'published_at' => $zh->published_at->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertSame('新標題', $zh->fresh()->title);
        $this->assertSame('English title', $en->fresh()->title);
    }

    #[Test]
    public function a_slug_must_be_unique_within_a_language_but_can_repeat_across_languages(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
        Post::factory()->create(['slug' => 'taken']);

        $this->actingAs($admin)->post('/admin/posts', ['title' => 'Dup', 'slug' => 'taken', 'body' => 'x'])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)->post('/admin/posts', [
            'title' => '譯文', 'slug' => 'taken', 'locale' => 'zh-Hant', 'translation_of' => 'taken', 'body' => 'x',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(2, Post::where('slug', 'taken')->count());
    }

    #[Test]
    public function an_unknown_language_is_rejected_and_english_posts_never_keep_a_translation_of(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);

        $this->actingAs($admin)->post('/admin/posts', ['title' => 'T', 'slug' => 's', 'locale' => 'fr', 'body' => 'x'])
            ->assertSessionHasErrors('locale');

        $this->actingAs($admin)->post('/admin/posts', ['title' => 'T', 'slug' => 'en-post', 'locale' => 'en', 'translation_of' => 'whatever', 'body' => 'x']);
        $this->assertNull(Post::where('slug', 'en-post')->first()->translation_of);
    }
}
