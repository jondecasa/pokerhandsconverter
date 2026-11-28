<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Support\StripePaymentDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramPaymentAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.token' => '123:test-token',
            'services.telegram.chat_id' => '555',
            'cashier.webhook.secret' => null,
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        Plan::factory()->create(['name' => 'Low Stakes Monthly', 'slug' => 'low', 'price' => 1.99, 'stripe_price_id' => 'price_low']);
        $this->user = User::factory()->create(['name' => 'Carlos <i>M</i>', 'email' => 'carlos@example.com', 'stripe_id' => 'cus_123']);
        $this->user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'sub_123', 'stripe_status' => 'past_due',
            'stripe_price' => 'price_low', 'quantity' => 1, 'trial_ends_at' => now()->subHour(),
        ]);
    }

    /** @param  array<string, mixed>  $invoice */
    private function webhook(string $type, array $invoice): void
    {
        $this->postJson('/stripe/webhook', [
            'id' => 'evt_'.uniqid(),
            'type' => $type,
            'livemode' => true,
            'data' => ['object' => $invoice + [
                'object' => 'invoice',
                'id' => 'in_123',
                'customer' => 'cus_123',
                'currency' => 'usd',
                'billing_reason' => 'subscription_cycle',
                'created' => now()->timestamp,
            ]],
        ])->assertOk();

        app(DeferredCallbackCollection::class)->invoke();
    }

    /** @return list<string> */
    private function sentMessages(): array
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://api.telegram.org/'))
            ->map(fn (array $pair) => $pair[0]['text'])
            ->values()
            ->all();
    }

    private function fakeDetails(?array $details): void
    {
        $this->app->instance(StripePaymentDetails::class, new class($details) extends StripePaymentDetails
        {
            public function __construct(private readonly ?array $fake) {}

            public function forInvoice(string $invoiceId): ?array
            {
                return $this->fake;
            }
        });
    }

    #[Test]
    public function a_successful_charge_is_announced_with_amount_customer_and_package(): void
    {
        $this->webhook('invoice.payment_succeeded', ['amount_paid' => 199]);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('💰 Cobro recibido', $messages[0]);
        $this->assertStringContainsString('1,99 USD — Low Stakes Monthly', $messages[0]);
        $this->assertStringContainsString('Carlos &lt;i&gt;M&lt;/i&gt; (carlos@example.com)', $messages[0]);
        $this->assertStringContainsString('Primer cobro tras la prueba gratuita', $messages[0]);
        $this->assertStringContainsString('https://dashboard.stripe.com/invoices/in_123', $messages[0]);
    }

    #[Test]
    public function the_zero_invoice_that_starts_a_trial_is_not_announced_as_a_charge(): void
    {
        $this->webhook('invoice.payment_succeeded', ['amount_paid' => 0, 'billing_reason' => 'subscription_create']);

        $this->assertSame([], $this->sentMessages());
    }

    #[Test]
    public function a_failed_payment_explains_the_decline_and_when_stripe_retries(): void
    {
        $this->fakeDetails([
            'payment_intent' => 'pi_999',
            'code' => 'card_declined',
            'decline_code' => 'insufficient_funds',
            'message' => 'Your card has insufficient funds.',
            'outcome_type' => 'issuer_declined',
            'seller_message' => 'The bank returned the decline code `insufficient_funds`.',
            'network_reason' => '51',
            'method' => 'Visa ····4242 debit (BR)',
        ]);
        Log::spy();

        $retry = now()->addDay()->setTime(20, 6);
        $this->webhook('invoice.payment_failed', [
            'amount_due' => 199,
            'attempt_count' => 1,
            'next_payment_attempt' => $retry->timestamp,
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/acct_1/live_abc',
        ]);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertStringContainsString('⚠️ Pago fallido', $message);
        $this->assertStringContainsString('1,99 USD — Low Stakes Monthly', $message);
        $this->assertStringContainsString('fondos insuficientes [card_declined / insufficient_funds / 51]', $message);
        $this->assertStringContainsString('Visa ····4242 debit (BR)', $message);
        $this->assertStringContainsString('Intento 1 — Stripe reintentará el '.$retry->copy()->utc()->format('d/m/Y H:i').' UTC', $message);
        $this->assertStringContainsString('https://dashboard.stripe.com/payments/pi_999', $message);
        $this->assertStringContainsString('https://invoice.stripe.com/i/acct_1/live_abc', $message);

        Log::shouldHaveReceived('warning')->withArgs(fn ($text, $context) => $text === 'Stripe payment failed'
            && $context['decline_code'] === 'insufficient_funds' && $context['invoice'] === 'in_123');
    }

    #[Test]
    public function a_failed_payment_is_still_announced_when_stripe_gives_no_details(): void
    {
        $this->fakeDetails(null);

        $this->webhook('invoice.payment_failed', ['amount_due' => 199, 'attempt_count' => 4, 'next_payment_attempt' => null]);

        $message = $this->sentMessages()[0];
        $this->assertStringContainsString('Motivo: no disponible', $message);
        $this->assertStringContainsString('Intento 4 — no hay más reintentos programados', $message);
    }

    #[Test]
    public function a_payment_blocked_by_radar_says_so(): void
    {
        $this->fakeDetails([
            'payment_intent' => 'pi_1', 'code' => 'card_declined', 'decline_code' => 'highest_risk_level', 'message' => null,
            'outcome_type' => 'blocked', 'seller_message' => null, 'network_reason' => null, 'method' => 'Link (monedero de Stripe)',
        ]);

        $this->webhook('invoice.payment_failed', ['amount_due' => 199, 'attempt_count' => 1]);

        $this->assertStringContainsString('bloqueado por Stripe/Radar, no por el banco', $this->sentMessages()[0]);
    }

    #[Test]
    public function a_payment_waiting_for_3d_secure_is_announced(): void
    {
        $this->webhook('invoice.payment_action_required', ['amount_due' => 199]);

        $this->assertStringContainsString('Pago pendiente de 3D Secure', $this->sentMessages()[0]);
    }

    #[Test]
    public function test_mode_links_point_to_the_test_dashboard_and_unrelated_events_are_ignored(): void
    {
        $this->postJson('/stripe/webhook', [
            'type' => 'invoice.payment_succeeded',
            'livemode' => false,
            'data' => ['object' => ['object' => 'invoice', 'id' => 'in_t', 'customer' => 'cus_123', 'currency' => 'eur', 'amount_paid' => 500]],
        ])->assertOk();
        $this->postJson('/stripe/webhook', ['type' => 'charge.refunded', 'data' => ['object' => ['object' => 'charge', 'id' => 'ch_1', 'customer' => 'cus_123']]])->assertOk();
        app(DeferredCallbackCollection::class)->invoke();

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('5,00 EUR', $messages[0]);
        $this->assertStringContainsString('https://dashboard.stripe.com/test/invoices/in_t', $messages[0]);
    }

    #[Test]
    public function nothing_is_sent_without_telegram_configured(): void
    {
        config(['services.telegram.token' => null]);
        $this->fakeDetails(null);

        $this->webhook('invoice.payment_succeeded', ['amount_paid' => 199]);
        $this->webhook('invoice.payment_failed', ['amount_due' => 199]);

        $this->assertSame([], $this->sentMessages());
    }

    #[Test]
    public function the_method_description_covers_cards_and_wallets(): void
    {
        $card = (object) ['type' => 'card', 'card' => (object) ['brand' => 'visa', 'last4' => '4242', 'funding' => 'credit', 'country' => 'ES']];

        $this->assertSame('Visa ····4242 credit (ES)', StripePaymentDetails::describeMethod($card));
        $this->assertSame('Link (monedero de Stripe)', StripePaymentDetails::describeMethod((object) ['type' => 'link']));
    }
}
