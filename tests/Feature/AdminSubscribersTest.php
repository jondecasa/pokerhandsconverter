<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSubscribersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    #[Test]
    public function guests_are_sent_to_login_from_the_admin_subscribers_page(): void
    {
        $this->get('/admin/subscribers')->assertRedirect('/login');
    }

    #[Test]
    public function logged_in_non_admins_get_403_from_the_admin_subscribers_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/subscribers')->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_subscribers_grouped_by_their_package(): void
    {
        $monthly = Plan::factory()->create(['name' => 'Monthly', 'slug' => 'monthly', 'stripe_price_id' => 'price_monthly123', 'price' => 9, 'currency' => 'USD', 'interval' => 'month']);
        $yearly = Plan::factory()->create(['name' => 'Yearly', 'slug' => 'yearly', 'stripe_price_id' => 'price_yearly123', 'price' => 90, 'currency' => 'USD', 'interval' => 'year']);

        $monthlySubscriber = User::factory()->create(['name' => 'Monthly Mo', 'email' => 'mo@example.com']);
        $yearlySubscriber = User::factory()->create(['name' => 'Yearly Yara', 'email' => 'yara@example.com']);
        $orphanSubscriber = User::factory()->create(['name' => 'Orphan Oli', 'email' => 'oli@example.com']);

        Subscription::factory()->for($monthlySubscriber, 'user')
            ->create(['type' => 'default', 'stripe_price' => $monthly->priceKey(), 'stripe_status' => 'active']);
        Subscription::factory()->for($yearlySubscriber, 'user')
            ->create(['type' => 'default', 'stripe_price' => $yearly->priceKey(), 'stripe_status' => 'trialing', 'trial_ends_at' => now()->addDays(7)]);
        Subscription::factory()->for($orphanSubscriber, 'user')
            ->create(['type' => 'default', 'stripe_price' => 'price_no_longer_configured', 'stripe_status' => 'active']);

        $response = $this->actingAs($this->admin())->get('/admin/subscribers');

        $response->assertOk()
            ->assertSee('Monthly Mo')
            ->assertSee('mo@example.com')
            ->assertSee('$9')
            ->assertSee('Yearly Yara')
            ->assertSee('yara@example.com')
            ->assertSee('$90')
            ->assertSee('Orphan Oli')
            ->assertSee('Other')
            ->assertSee('Unmatched')
            ->assertSee('price_no_longer_configured');
    }
}
