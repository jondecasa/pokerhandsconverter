<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\SubscriptionAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignupAttributionTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $override = []): void
    {
        $this->post('/register', array_merge([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'a-long-password-1',
            'password_confirmation' => 'a-long-password-1',
        ], $override));
    }

    #[Test]
    public function an_account_remembers_the_referrer_utm_tags_and_landing_page(): void
    {
        $this->withHeader('Referer', 'https://www.google.com/search?q=vpip')
            ->get('/blog?utm_source=telegram&utm_medium=social&utm_campaign=vpip-post');

        $this->register();

        $this->assertSame([
            'referrer' => 'google.com',
            'utm_source' => 'telegram',
            'utm_medium' => 'social',
            'utm_campaign' => 'vpip-post',
            'landing' => '/blog',
        ], User::firstWhere('email', 'ana@example.com')->signup_attribution);
    }

    #[Test]
    public function only_the_first_visit_counts(): void
    {
        $this->withHeader('Referer', 'https://news.ycombinator.com/item?id=1')->get('/');
        $this->withHeader('Referer', 'https://www.google.com/')->get('/pricing?utm_source=later');

        $this->register();

        $attribution = User::firstWhere('email', 'ana@example.com')->signup_attribution;
        $this->assertSame('news.ycombinator.com', $attribution['referrer']);
        $this->assertSame('/', $attribution['landing']);
        $this->assertArrayNotHasKey('utm_source', $attribution);
    }

    #[Test]
    public function links_from_the_site_itself_are_not_a_referrer(): void
    {
        $this->withHeader('Referer', config('app.url').'/blog')->get('/pricing');

        $this->register();

        $attribution = User::firstWhere('email', 'ana@example.com')->signup_attribution;
        $this->assertArrayNotHasKey('referrer', $attribution);
        $this->assertSame('/pricing', $attribution['landing']);
    }

    #[Test]
    public function tags_are_cleaned_and_the_referrer_path_is_never_kept(): void
    {
        $this->withHeader('Referer', 'https://example.org/private/path?token=secret')
            ->get('/?utm_source='.urlencode('<script>x</script> a/b').'&utm_medium='.str_repeat('m', 300));

        $this->register();

        $attribution = User::firstWhere('email', 'ana@example.com')->signup_attribution;
        $this->assertSame('example.org', $attribution['referrer']);
        $this->assertSame('scriptxscript ab', $attribution['utm_source']);
        $this->assertSame(80, mb_strlen($attribution['utm_medium']));
        $this->assertStringNotContainsString('secret', json_encode($attribution));
    }

    #[Test]
    public function an_account_without_a_tracked_visit_still_registers(): void
    {
        $this->register();

        $this->assertNull(User::firstWhere('email', 'ana@example.com')->signup_attribution);
    }

    #[Test]
    public function the_telegram_message_says_where_the_subscriber_came_from(): void
    {
        Plan::factory()->create(['name' => 'Free starter', 'slug' => 'free', 'price' => 0, 'stripe_price_id' => null]);
        $user = User::factory()->create([
            'signup_attribution' => ['referrer' => 'google.com', 'utm_source' => 'telegram', 'landing' => '/blog/x'],
        ]);
        $subscription = $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'sub_1', 'stripe_status' => 'active', 'stripe_price' => 'price_pro', 'quantity' => 1,
        ]);

        $message = app(SubscriptionAlert::class)->message($subscription);

        $this->assertStringContainsString('Origen: telegram → /blog/x', $message);
    }

    #[Test]
    public function the_privacy_page_mentions_the_sign_up_source(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('Sign-up source');
    }
}
