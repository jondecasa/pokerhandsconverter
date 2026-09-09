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
            ->assertSee('PokerCoinverter')
            ->assertSee('Start your '.config('pokercoinverter.trial_days').'-day free trial');
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
}
