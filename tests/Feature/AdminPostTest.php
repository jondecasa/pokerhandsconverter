<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPostTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    #[Test]
    public function guests_are_sent_to_login_from_the_admin_posts_area(): void
    {
        $this->get('/admin/posts')->assertRedirect('/login');
    }

    #[Test]
    public function logged_in_non_admins_get_403_from_the_admin_posts_area(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/posts')->assertForbidden();
        $this->actingAs($user)->post('/admin/posts', [])->assertForbidden();
    }

    #[Test]
    public function an_admin_can_see_the_posts_list(): void
    {
        Post::factory()->create(['title' => 'How to import CoinPoker hands']);

        $this->actingAs($this->admin())->get('/admin/posts')
            ->assertOk()
            ->assertSee('How to import CoinPoker hands');
    }

    #[Test]
    public function an_admin_can_create_a_published_post(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/posts', [
            'title' => 'Importing CoinPoker into PT4',
            'slug' => 'importing-coinpoker-into-pt4',
            'excerpt' => 'A quick walkthrough.',
            'body' => "## Step 1\n\nExport your hands.",
            'is_published' => '1',
        ]);

        $post = Post::where('slug', 'importing-coinpoker-into-pt4')->firstOrFail();
        $response->assertRedirect(route('admin.posts.edit', $post));

        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->isPublished());
        $this->assertStringContainsString('<h2>Step 1</h2>', $post->bodyHtml());
    }

    #[Test]
    public function creating_a_post_as_a_draft_leaves_it_unpublished(): void
    {
        $this->actingAs($this->admin())->post('/admin/posts', [
            'title' => 'Draft post',
            'slug' => 'draft-post',
            'body' => 'Work in progress.',
        ])->assertRedirect();

        $post = Post::where('slug', 'draft-post')->firstOrFail();
        $this->assertFalse($post->is_published);
        $this->assertNull($post->published_at);
        $this->assertFalse($post->isPublished());
    }

    #[Test]
    public function a_scheduled_future_date_is_not_published_yet(): void
    {
        $this->actingAs($this->admin())->post('/admin/posts', [
            'title' => 'Future post',
            'slug' => 'future-post',
            'body' => 'Coming soon.',
            'is_published' => '1',
            'published_at' => now()->addWeek()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $post = Post::where('slug', 'future-post')->firstOrFail();
        $this->assertTrue($post->is_published);
        $this->assertTrue($post->published_at->isFuture());
        $this->assertFalse($post->isPublished());
    }

    #[Test]
    public function updating_a_post_keeps_its_original_published_at_when_unchanged(): void
    {
        // datetime-local input has no seconds field, so round-tripping through
        // the form truncates to the minute — start from a round timestamp.
        $post = Post::factory()->published()->create(['published_at' => now()->subDay()->startOfMinute()]);
        $originalPublishedAt = $post->published_at;

        $this->actingAs($this->admin())->put("/admin/posts/{$post->slug}", [
            'title' => 'Updated title',
            'slug' => $post->slug,
            'body' => $post->body,
            'is_published' => '1',
            'published_at' => $originalPublishedAt->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.posts.edit', $post));

        $this->assertSame('Updated title', $post->fresh()->title);
        $this->assertEquals($originalPublishedAt->timestamp, $post->fresh()->published_at->timestamp);
    }

    #[Test]
    public function an_admin_can_delete_a_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin())->delete("/admin/posts/{$post->slug}")
            ->assertRedirect(route('admin.posts.index'));

        $this->assertModelMissing($post);
    }
}
