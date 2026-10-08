<?php

namespace Tests\Feature;

use App\Support\CloudflareIps;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudflareProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test-ip', fn () => request()->ip());
    }

    #[Test]
    public function the_visitor_ip_is_read_from_a_request_that_comes_through_cloudflare(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.69.214.88'])
            ->get('/_test-ip', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertOk()
            ->assertContent('203.0.113.9');
    }

    #[Test]
    public function a_forged_forwarded_for_header_cannot_hide_the_visitor_behind_cloudflare(): void
    {
        // Cloudflare appends the real client to whatever the client sent.
        $this->withServerVariables(['REMOTE_ADDR' => '104.22.136.95'])
            ->get('/_test-ip', ['X-Forwarded-For' => '1.2.3.4, 203.0.113.9'])
            ->assertContent('203.0.113.9');
    }

    #[Test]
    public function an_ipv6_cloudflare_edge_is_trusted_too(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '2606:4700:10::1'])
            ->get('/_test-ip', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertContent('203.0.113.9');
    }

    #[Test]
    public function a_direct_request_to_the_origin_cannot_forge_its_ip(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.5'])
            ->get('/_test-ip', ['X-Forwarded-For' => '203.0.113.9'])
            ->assertContent('198.51.100.5');
    }

    #[Test]
    public function the_trusted_ranges_are_valid_cidr_blocks(): void
    {
        foreach (CloudflareIps::ranges() as $range) {
            $this->assertMatchesRegularExpression('#^[0-9a-f:.]+/\d{1,3}$#i', $range);
        }
    }
}
