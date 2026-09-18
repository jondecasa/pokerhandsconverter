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
    public function the_posts_list_can_be_searched_by_title_slug_or_excerpt(): void
    {
        Post::factory()->create(['title' => 'Understanding rakeback', 'slug' => 'rakeback-basics', 'excerpt' => 'Cash back on rake.']);
        Post::factory()->create(['title' => 'Bankroll rules', 'slug' => 'bankroll-rules', 'excerpt' => 'Sizing your roll.']);
        Post::factory()->create(['title' => 'HUD stats', 'slug' => 'hud-stats', 'excerpt' => 'Reading VPIP and PFR.']);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/posts?q=rakeback')
            ->assertOk()
            ->assertSee('Understanding rakeback')
            ->assertDontSee('Bankroll rules')
            ->assertDontSee('HUD stats')
            ->assertSee('1 of 3 posts match');

        $this->actingAs($admin)->get('/admin/posts?q=bankroll-rules')->assertSee('Bankroll rules')->assertDontSee('HUD stats');
        $this->actingAs($admin)->get('/admin/posts?q=VPIP')->assertSee('HUD stats')->assertDontSee('Bankroll rules');
        $this->actingAs($admin)->get('/admin/posts?q=nothing-matches')->assertOk()->assertSee('No posts match');
        $this->actingAs($admin)->get('/admin/posts')->assertSee('Understanding rakeback')->assertSee('Bankroll rules')->assertSee('HUD stats');
    }

    #[Test]
    public function each_row_has_an_edit_link_and_a_delete_button_that_opens_the_confirm_modal(): void
    {
        $post = Post::factory()->create(['title' => 'Row actions']);

        $this->actingAs($this->admin())->get('/admin/posts')
            ->assertOk()
            ->assertSee('href="'.route('admin.posts.edit', $post).'"', false)
            ->assertSee('confirm-delete', false)
            ->assertSee('Delete this post?', false)
            ->assertDontSee('confirm(', false);
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

        $this->actingAs($this->admin())->put("/admin/posts/{$post->id}", [
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

        $this->actingAs($this->admin())->delete("/admin/posts/{$post->id}")
            ->assertRedirect(route('admin.posts.index'));

        $this->assertModelMissing($post);
    }
}
