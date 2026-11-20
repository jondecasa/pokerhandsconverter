<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudflareAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const BEACON = 'static.cloudflareinsights.com/beacon.min.js';

    #[Test]
    public function the_public_and_access_pages_carry_the_beacon_once_a_token_is_set(): void
    {
        config(['services.cloudflare.analytics_token' => 'abc123token']);

        foreach (['/', '/blog', '/zh-hant', '/login', '/register'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee(self::BEACON, false)
                ->assertSee('&quot;token&quot;:&quot;abc123token&quot;', false);
        }

        $this->get('/privacy')->assertSee('Visit statistics');
    }

    #[Test]
    public function nothing_is_added_without_a_token(): void
    {
        config(['services.cloudflare.analytics_token' => null]);

        foreach (['/', '/login', '/privacy'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('cloudflareinsights', false);
        }

        $this->get('/privacy')->assertDontSee('Cloudflare');
    }

    #[Test]
    public function the_signed_in_app_is_not_measured(): void
    {
        config(['services.cloudflare.analytics_token' => 'abc123token']);

        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get('/dashboard')->assertOk()->assertDontSee('cloudflareinsights', false);
    }
}
