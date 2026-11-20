<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WwwRedirectTest extends TestCase
{
    #[Test]
    public function www_pages_redirect_permanently_to_the_bare_domain_keeping_path_and_query(): void
    {
        $this->get('https://www.example.com/blog?page=2')
            ->assertStatus(301)
            ->assertRedirect('https://example.com/blog?page=2');

        $this->get('https://www.example.com/zh-hant/blog/some-post')
            ->assertStatus(301)
            ->assertRedirect('https://example.com/zh-hant/blog/some-post');
    }

    #[Test]
    public function the_scheme_is_kept(): void
    {
        $this->get('http://www.example.com/')->assertRedirect('http://example.com/');
    }

    #[Test]
    public function a_post_keeps_its_method_with_a_308(): void
    {
        $this->post('https://www.example.com/webhook-like')->assertStatus(308);
    }

    #[Test]
    public function the_bare_domain_and_other_hosts_are_not_redirected(): void
    {
        $this->get('https://example.com/up')->assertOk();
        $this->get('http://localhost/up')->assertOk();
        // only a real www. prefix counts: "www.com" has no domain behind it
        $this->get('http://www.com/up')->assertOk();
    }
}
