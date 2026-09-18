<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
