<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function urls(): array
    {
        return [
            'home' => ['/'],
            'chinese home' => ['/zh-hant'],
            'login' => ['/login'],
            'api (unauthorised)' => ['/api/posts'],
            'not found' => ['/this-page-does-not-exist'],
        ];
    }

    #[Test]
    #[DataProvider('urls')]
    public function every_response_carries_the_security_headers(string $url): void
    {
        $this->get($url)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    #[Test]
    public function a_header_set_by_the_page_itself_is_not_overwritten(): void
    {
        $response = (new SecurityHeaders)->handle(
            Request::create('/'),
            fn () => (new Response('ok'))->header('X-Frame-Options', 'DENY'),
        );

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    #[Test]
    public function a_file_download_response_gets_the_headers_too(): void
    {
        Route::get('/_test-download', fn () => response()->streamDownload(fn () => print ('data'), 'x.txt'));

        $this->get('/_test-download')->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
