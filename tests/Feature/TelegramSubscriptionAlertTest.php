<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramSubscriptionAlertTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        config(['services.telegram.token' => '123:test-token', 'services.telegram.chat_id' => '555']);
    }

    private function user(): User
    {
        return User::factory()->create(['name' => 'Ana <b>Pérez</b>', 'email' => 'ana@example.com', 'email_verified_at' => now()]);
    }

    private function subscribe(User $user, string $status, string $price = 'price_pro', ?\DateTimeInterface $trialEnds = null)
    {
        return $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => $status,
            'stripe_price' => $price,
            'quantity' => 1,
            'trial_ends_at' => $trialEnds,
        ]);
    }

    /** @return list<Request> */
    private function telegramRequests(): array
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://api.telegram.org/'))
            ->map(fn (array $pair) => $pair[0])
            ->all();
    }

    #[Test]
    public function a_free_package_sends_the_owner_a_telegram_message(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Plan::factory()->create(['name' => 'Free starter', 'slug' => 'free', 'price' => 0, 'stripe_price_id' => null]);

        $this->actingAs($this->user())->post('/subscribe/free')->assertRedirect(route('subscription.success'));

        $sent = $this->telegramRequests();
        $this->assertCount(1, $sent);
        $this->assertSame('https://api.telegram.org/bot123:test-token/sendMessage', $sent[0]->url());
        $this->assertSame('555', $sent[0]['chat_id']);
        $this->assertStringContainsString('Nueva suscripción', $sent[0]['text']);
        $this->assertStringContainsString('ana@example.com', $sent[0]['text']);
        $this->assertStringContainsString('Free starter — gratis', $sent[0]['text']);
        // names are escaped so they cannot inject Telegram markup
        $this->assertStringContainsString('Ana &lt;b&gt;Pérez&lt;/b&gt;', $sent[0]['text']);
    }

    #[Test]
    public function a_paid_package_in_trial_says_so(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Plan::factory()->create(['name' => 'Pro', 'slug' => 'pro', 'price' => 9.99, 'currency' => 'EUR', 'interval' => 'month', 'stripe_price_id' => 'price_pro']);

        $this->subscribe($this->user(), 'trialing', 'price_pro', now()->addDays(7));
        app(DeferredCallbackCollection::class)->invoke();

        $sent = $this->telegramRequests();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('Pro — 9.99 EUR / mes', $sent[0]['text']);
        $this->assertStringContainsString('En prueba gratuita hasta el '.now()->addDays(7)->format('d/m/Y'), $sent[0]['text']);
    }

    #[Test]
    public function an_incomplete_subscription_alerts_once_when_it_becomes_active(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $subscription = $this->subscribe($this->user(), 'incomplete');
        app(DeferredCallbackCollection::class)->invoke();
        $this->assertCount(0, $this->telegramRequests());

        $subscription->update(['stripe_status' => 'active']);
        app(DeferredCallbackCollection::class)->invoke();
        $this->assertCount(1, $this->telegramRequests());

        // later changes (cancelling, a failed payment that recovers) are not new subscriptions
        $subscription->update(['quantity' => 2, 'ends_at' => now()->addMonth()]);
        $subscription->update(['stripe_status' => 'past_due']);
        $subscription->update(['stripe_status' => 'active']);
        app(DeferredCallbackCollection::class)->invoke();
        $this->assertCount(1, $this->telegramRequests());
    }

    #[Test]
    public function nothing_is_sent_without_a_token_and_chat_id(): void
    {
        config(['services.telegram.token' => null, 'services.telegram.chat_id' => null]);
        Http::fake();

        $this->subscribe($this->user(), 'active');
        app(DeferredCallbackCollection::class)->invoke();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_failing_telegram_never_breaks_the_signup(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 403)]);
        Plan::factory()->create(['slug' => 'free', 'price' => 0, 'stripe_price_id' => null]);

        $user = $this->user();
        $this->actingAs($user)->post('/subscribe/free')->assertRedirect(route('subscription.success'));

        $this->assertTrue($user->fresh()->subscribed('default'));
    }

    #[Test]
    public function the_test_command_lists_chat_ids_when_none_is_set(): void
    {
        config(['services.telegram.token' => '123:test-token', 'services.telegram.chat_id' => null]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [
            ['update_id' => 1, 'message' => ['chat' => ['id' => 987654, 'first_name' => 'Jon', 'type' => 'private']]],
        ]])]);

        $this->artisan('telegram:test')->expectsOutputToContain('987654')->assertSuccessful();
    }
}
