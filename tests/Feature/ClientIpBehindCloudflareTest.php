<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientIpBehindCloudflareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_client-ip', fn (Request $request) => $request->ip());
    }

    /** @param  array<string, string>  $server */
    private function ipSeenBy(array $server): string
    {
        return $this->call('GET', '/_client-ip', [], [], [], $server)->getContent();
    }

    #[Test]
    public function the_visitor_is_found_behind_cloudflare_and_the_reverse_proxy(): void
    {
        // Caddy (a Docker address) saw Cloudflare (172.71.x is Cloudflare) and wrote it into X-Forwarded-For
        $this->assertSame('203.0.113.9', $this->ipSeenBy([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_X_FORWARDED_FOR' => '172.71.1.1',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.9',
        ]));
    }

    #[Test]
    public function the_visitor_is_found_when_cloudflare_connects_straight_to_the_app(): void
    {
        $this->assertSame('203.0.113.9', $this->ipSeenBy([
            'REMOTE_ADDR' => '104.16.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.9',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.9',
        ]));
    }

    #[Test]
    public function a_forged_cloudflare_header_on_a_direct_visit_is_ignored(): void
    {
        // the reverse proxy saw 198.51.100.7 itself, so that is the visitor whatever the header claims
        $this->assertSame('198.51.100.7', $this->ipSeenBy([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.9',
        ]));

        $this->assertSame('198.51.100.7', $this->ipSeenBy([
            'REMOTE_ADDR' => '198.51.100.7',
            'HTTP_CF_CONNECTING_IP' => '203.0.113.9',
        ]));
    }

    #[Test]
    public function a_visit_through_only_the_reverse_proxy_still_gives_the_real_address(): void
    {
        $this->assertSame('198.51.100.7', $this->ipSeenBy([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
        ]));
    }

    #[Test]
    public function an_invalid_cloudflare_header_is_ignored(): void
    {
        $this->assertSame('172.71.1.1', $this->ipSeenBy([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_X_FORWARDED_FOR' => '172.71.1.1',
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
        ]));
    }
}
