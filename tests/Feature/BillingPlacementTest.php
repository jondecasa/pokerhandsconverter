<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingPlacementTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ])->items()->create([
            'stripe_id' => 'si_'.uniqid(),
            'stripe_product' => 'prod_test',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        return $user;
    }

    #[Test]
    public function the_dashboard_has_no_subscription_block(): void
    {
        $this->actingAs($this->subscribedUser())->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Manage billing')
            ->assertDontSee('Subscription')
            ->assertDontSee(route('subscription.cancel'), false);
    }

    #[Test]
    public function the_profile_page_offers_billing_and_a_tucked_away_cancel(): void
    {
        $this->actingAs($this->subscribedUser())->get('/profile')
            ->assertOk()
            ->assertSee('Manage billing')
            ->assertSee('More options')
            ->assertSee(route('subscription.cancel'), false);
    }

    #[Test]
    public function an_unsubscribed_user_sees_plans_and_no_cancel_on_the_profile(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))->get('/profile')
            ->assertOk()
            ->assertSee('See plans')
            ->assertDontSee(route('subscription.cancel'), false);
    }
}
