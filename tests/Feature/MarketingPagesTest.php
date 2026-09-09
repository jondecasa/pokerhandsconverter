<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->get('/pricing')->assertOk();

        foreach (config('pokercoinverter.plans') as $plan) {
            $response->assertSee($plan['name']);
        }
    }

    #[Test]
    public function the_legal_pages_render(): void
    {
        $this->get('/terms')->assertOk()->assertSee('Terms of Service');
        $this->get('/privacy')->assertOk()->assertSee('Privacy Policy');
    }
}
