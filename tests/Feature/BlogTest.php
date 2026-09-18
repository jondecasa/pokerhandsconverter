<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_blog_index_lists_only_published_posts(): void
    {
        Post::factory()->published()->create(['title' => 'Live Post']);
        Post::factory()->create(['title' => 'Draft Post']);
        Post::factory()->create(['title' => 'Future Post', 'is_published' => true, 'published_at' => now()->addWeek()]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Live Post')
            ->assertDontSee('Draft Post')
            ->assertDontSee('Future Post');
    }

    #[Test]
    public function the_blog_index_shows_ten_posts_per_page_and_no_intro_line(): void
    {
        Post::factory()->published()->count(11)->create();

        $this->get('/blog')
            ->assertOk()
            ->assertDontSee('Guides and notes on getting');

        $this->assertSame(10, substr_count($this->get('/blog')->getContent(), 'Read more'));
        $this->assertSame(1, substr_count($this->get('/blog?page=2')->getContent(), 'Read more'));
    }

    #[Test]
    public function the_blog_pagination_uses_the_brand_buttons_with_no_dark_mode_variants(): void
    {
        Post::factory()->published()->count(11)->create();

        $html = $this->get('/blog')->assertOk()
            ->assertSee('aria-label="Pagination"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('bg-indigo-600', false)
            ->getContent();

        preg_match('#<nav role="navigation" aria-label="Pagination".*?</nav>#s', $html, $nav);

        $this->assertNotEmpty($nav);
        $this->assertStringNotContainsString('dark:', $nav[0]);
    }

    #[Test]
    public function a_published_post_renders_with_its_markdown_body_and_structured_data(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Importing CoinPoker Hands',
            'body' => "## Step one\n\nDo the thing.",
        ]);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Importing CoinPoker Hands')
            ->assertSee('<h2>Step one</h2>', false)
            ->assertSee('BlogPosting', false)
            ->assertSee('<link rel="canonical" href="'.route('blog.show', $post).'">', false);
    }

    #[Test]
    public function a_script_closing_tag_in_a_title_cannot_break_out_of_the_structured_data(): void
    {
        $post = Post::factory()->published()->create(['title' => '</script><script>alert(1)</script>']);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertDontSee('</script><script>alert(1)', false)
            ->assertSee('</script>', false);
    }

    #[Test]
    public function guests_get_a_404_for_an_unpublished_post(): void
    {
        $post = Post::factory()->create(['is_published' => false]);

        $this->get(route('blog.show', $post))->assertNotFound();
    }

    #[Test]
    public function an_admin_can_preview_an_unpublished_post(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
        $post = Post::factory()->create(['is_published' => false, 'title' => 'Draft Preview']);

        $this->actingAs($admin)->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Draft Preview')
            ->assertSee('Preview only');
    }
}
