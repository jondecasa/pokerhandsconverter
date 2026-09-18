<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Post;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    #[Test]
    public function the_landing_page_renders_for_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('PokerHandsConverter')
            ->assertSee('Start your '.config('pokerhandsconverter.trial_days').'-day free trial');
    }

    #[Test]
    public function the_public_pricing_page_renders_for_guests(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Monthly')
            ->assertSee('Yearly');
    }

    #[Test]
    public function hidden_packages_are_not_listed_publicly(): void
    {
        Plan::factory()->hidden()->create(['name' => 'Secret Whale Deal']);

        $this->get('/pricing')->assertOk()->assertDontSee('Secret Whale Deal');
    }

    #[Test]
    public function the_legal_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertSee('Terms of Service');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
    }

    #[Test]
    public function the_sitemap_lists_the_public_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        foreach (['home', 'pricing', 'contact', 'terms', 'privacy'] as $routeName) {
            $response->assertSee(route($routeName), false);
        }
    }

    #[Test]
    public function the_sitemap_is_well_formed_xml_that_includes_published_posts(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'a-live-post']);
        Post::factory()->create(['slug' => 'a-draft-post']);

        $content = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);

        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml);

        $locs = array_map(fn ($url) => (string) $url->loc, iterator_to_array($xml->url, false));

        $this->assertContains(route('blog.show', $post), $locs);
        $this->assertNotContains(route('blog.show', 'a-draft-post'), $locs);
        $this->assertNotEmpty((string) $xml->url[count($xml->url) - 1]->lastmod);
    }

    #[Test]
    public function no_blade_view_contains_a_literal_php_open_tag_other_than_php(): void
    {
        // Blade tokenizes views with PHP's tokenizer, so on a server with
        // short_open_tag enabled a literal "<?xml" or "<?=" in a view is parsed
        // as PHP and breaks the page. That setting is off locally, so guard it.
        foreach (File::allFiles(resource_path('views')) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/<\?(?!php\b)/',
                $file->getContents(),
                "{$file->getRelativePathname()} contains a literal \"<?\" that is not \"<?php\"."
            );
        }
    }

    #[Test]
    public function every_structured_data_block_is_valid_schema_org_json(): void
    {
        $post = Post::factory()->published()->create(['title' => 'A </script> title']);

        foreach (['/', '/pricing', route('blog.show', $post)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocks);

            $this->assertNotEmpty($blocks[1], "No JSON-LD found on {$url}");

            foreach ($blocks[1] as $raw) {
                $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

                $this->assertSame('https://schema.org', $data['@context'] ?? null, "Bad @context on {$url}: ".substr($raw, 0, 80));
                $this->assertNotEmpty($data['@type'] ?? null, "Missing @type on {$url}");

                if ($data['@type'] === 'BlogPosting') {
                    $this->assertSame('PokerHandsConverter', $data['author']['name'] ?? null);
                    $this->assertSame([asset('images/og-image.png')], $data['image'] ?? null);
                }
            }
        }
    }

    #[Test]
    public function marketing_pages_expose_a_canonical_link_and_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('FAQPage', false)
            ->assertSee('SoftwareApplication', false);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/pricing').'">', false)
            ->assertSee('FAQPage', false);
    }
}
