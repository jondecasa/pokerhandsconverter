<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    private function authed(): static
    {
        config(['pokerhandsconverter.blog_api.token' => 'secret-token']);

        return $this->withToken('secret-token');
    }

    #[Test]
    public function the_api_does_not_exist_until_a_token_is_configured(): void
    {
        config(['pokerhandsconverter.blog_api.token' => null]);

        $this->getJson('/api/posts')->assertNotFound();
        $this->withToken('anything')->postJson('/api/posts', [])->assertNotFound();
    }

    #[Test]
    public function requests_without_a_valid_token_are_rejected(): void
    {
        config(['pokerhandsconverter.blog_api.token' => 'secret-token']);

        $this->getJson('/api/posts')->assertUnauthorized();
        $this->withToken('wrong')->postJson('/api/posts', ['slug' => 'x', 'title' => 'X', 'body' => 'x'])->assertUnauthorized();
        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function a_new_post_is_created_as_a_draft_by_default(): void
    {
        $this->authed()->postJson('/api/posts', [
            'slug' => 'my-first-post',
            'title' => 'My first post',
            'body' => "## Hello\n\nWorld.",
        ])
            ->assertCreated()
            ->assertJsonPath('created', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.url', route('blog.show', 'my-first-post'));

        $this->assertDatabaseHas('posts', ['slug' => 'my-first-post', 'is_published' => false]);
        $this->get('/blog/my-first-post')->assertNotFound();
    }

    #[Test]
    public function publishing_without_a_date_publishes_immediately(): void
    {
        $this->authed()->postJson('/api/posts', [
            'slug' => 'live-post',
            'title' => 'Live post',
            'body' => 'Some body text.',
            'is_published' => true,
        ])->assertCreated()->assertJsonPath('data.status', 'published');

        $this->get('/blog/live-post')->assertOk()->assertSee('Live post');
    }

    #[Test]
    public function a_future_published_at_schedules_the_post(): void
    {
        $this->authed()->postJson('/api/posts', [
            'slug' => 'later-post',
            'title' => 'Later post',
            'body' => 'Body.',
            'is_published' => true,
            'published_at' => now()->addWeek()->toIso8601String(),
        ])->assertCreated()->assertJsonPath('data.status', 'scheduled');

        $this->get('/blog/later-post')->assertNotFound();
    }

    #[Test]
    public function posting_an_existing_slug_updates_only_the_fields_sent(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'existing',
            'title' => 'Old title',
            'excerpt' => 'Keep this excerpt',
        ]);
        $publishedAt = $post->published_at->timestamp;

        $this->authed()->postJson('/api/posts', ['slug' => 'existing', 'title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('data.status', 'published');

        $post->refresh();
        $this->assertSame('New title', $post->title);
        $this->assertSame('Keep this excerpt', $post->excerpt);
        $this->assertTrue($post->is_published);
        $this->assertSame($publishedAt, $post->published_at->timestamp);
        $this->assertDatabaseCount('posts', 1);
    }

    #[Test]
    public function a_post_can_be_unpublished_through_the_api(): void
    {
        Post::factory()->published()->create(['slug' => 'retire-me']);

        $this->authed()->postJson('/api/posts', ['slug' => 'retire-me', 'is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');

        $this->get('/blog/retire-me')->assertNotFound();
    }

    #[Test]
    public function creating_requires_a_title_a_body_and_a_url_safe_slug(): void
    {
        $this->authed()->postJson('/api/posts', ['slug' => 'no-content'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);

        $this->authed()->postJson('/api/posts', ['slug' => 'Not A Slug', 'title' => 'T', 'body' => 'B'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function the_index_lists_posts_with_their_status_and_url(): void
    {
        Post::factory()->published()->create(['slug' => 'one']);
        Post::factory()->create(['slug' => 'two']);

        $this->authed()->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['slug' => 'one', 'status' => 'published', 'url' => route('blog.show', 'one')])
            ->assertJsonFragment(['slug' => 'two', 'status' => 'draft']);
    }
}
