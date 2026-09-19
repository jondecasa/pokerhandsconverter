<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoBasicsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> */
    public static function guestPages(): array
    {
        return [
            'login' => ['/login', 'Log in to PokerHandsConverter'],
            'register' => ['/register', 'Create your PokerHandsConverter account'],
            'forgot password' => ['/forgot-password', 'Reset your password'],
        ];
    }

    #[Test]
    #[DataProvider('guestPages')]
    public function the_access_pages_have_a_title_a_description_and_an_h1(string $url, string $heading): void
    {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<title>[^<]*'.preg_quote($heading, '#').'#', $html);
        $this->assertMatchesRegularExpression('#<meta name="description" content="[^"]{40,}"#', $html);
        $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html));
    }

    #[Test]
    public function the_access_pages_do_not_share_one_description(): void
    {
        $descriptions = collect(['/login', '/register', '/forgot-password'])
            ->map(fn (string $url) => preg_match('#<meta name="description" content="([^"]*)"#', $this->get($url)->getContent(), $m) ? $m[1] : null);

        $this->assertCount(3, $descriptions->unique());
    }

    #[Test]
    public function every_page_title_stays_within_seventy_characters(): void
    {
        Post::factory()->published()->create(['title' => 'A post', 'slug' => 'a-post', 'meta_title' => null]);

        foreach (['/', '/zh-hant', '/pricing', '/contact', '/blog', '/blog/a-post', '/login', '/register', '/forgot-password'] as $url) {
            preg_match('#<title>(.*?)</title>#s', $this->get($url)->getContent(), $m);
            $title = html_entity_decode($m[1] ?? '', ENT_QUOTES);

            $this->assertLessThanOrEqual(70, mb_strlen($title), "$url title is too long: $title");
        }
    }

    #[Test]
    public function robots_txt_keeps_crawlers_out_of_cloudflare_internal_paths_and_lists_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /cdn-cgi/', $robots);
        $this->assertStringContainsString('Sitemap: https://pokerhandsconverter.com/sitemap.xml', $robots);
    }
}
