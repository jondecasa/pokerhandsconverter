<?php

namespace Tests\Feature;

use App\Http\Controllers\SubscriptionController;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression guard: a paid-plan checkout hands back a Laravel\Cashier\Checkout
     * (Cashier's SubscriptionBuilder::checkout() return type), not a
     * RedirectResponse/Redirector. A return type that doesn't allow it makes PHP
     * throw a TypeError on every paid-plan subscribe — see the 500 this once was.
     */
    #[Test]
    public function checkout_return_type_accepts_a_cashier_checkout_response(): void
    {
        $type = (new ReflectionMethod(SubscriptionController::class, 'checkout'))->getReturnType();

        $this->assertNotNull($type, 'checkout() should declare a return type.');
        $this->assertStringContainsString(
            Checkout::class,
            (string) $type,
            'checkout() must allow returning a Laravel\Cashier\Checkout, or a real paid-plan subscribe throws a TypeError.'
        );
    }

    #[Test]
    public function a_user_who_never_had_a_trial_has_not_used_one(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasUsedTrial());
    }

    #[Test]
    public function a_past_trial_subscription_counts_as_used_even_if_cancelled(): void
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_old',
            'quantity' => 1,
            'trial_ends_at' => now()->subDays(10),
            'ends_at' => now()->subDays(3),
        ]);

        $this->assertTrue($user->hasUsedTrial());
    }

    #[Test]
    public function a_free_package_subscription_does_not_count_as_a_used_trial(): void
    {
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'free_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'free:starter',
            'quantity' => 1,
        ]);

        $this->assertFalse($user->hasUsedTrial());
    }

    #[Test]
    public function the_pricing_page_offers_the_trial_only_once(): void
    {
        $plan = Plan::factory()->create(['trial_days' => 7, 'stripe_price_id' => 'price_x']);

        $fresh = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($fresh)->get('/account/plans')
            ->assertSee('Start 7-day free trial')
            ->assertDontSee('already used your free trial');

        $returning = User::factory()->create(['email_verified_at' => now()]);
        $returning->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_old',
            'quantity' => 1,
            'trial_ends_at' => now()->subDays(10),
            'ends_at' => now()->subDays(3),
        ]);

        $response = $this->actingAs($returning)->get('/account/plans');
        $response->assertDontSee("Start {$plan->trial_days}-day free trial");
        $response->assertSee('Choose '.$plan->name);
        $response->assertSee('already used your free trial');
    }
}
