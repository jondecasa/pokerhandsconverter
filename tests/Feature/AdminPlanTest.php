<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPlanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    #[Test]
    public function guests_are_sent_to_login_from_the_admin_area(): void
    {
        $this->get('/admin/plans')->assertRedirect('/login');
    }

    #[Test]
    public function logged_in_non_admins_get_403_from_the_admin_area(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/admin/plans')->assertForbidden();
        $this->actingAs($user)->post('/admin/plans', [])->assertForbidden();
    }

    #[Test]
    public function an_admin_can_see_the_packages_list(): void
    {
        Plan::factory()->create(['name' => 'Mid Stakes']);

        $this->actingAs($this->admin())->get('/admin/plans')
            ->assertOk()
            ->assertSee('Mid Stakes');
    }

    #[Test]
    public function an_admin_can_create_a_package_with_a_stake_and_visibility(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/plans', [
            'name' => 'High Stakes',
            'slug' => 'high-stakes',
            'description' => 'For the grinders.',
            'price' => '49.00',
            'currency' => 'usd',
            'interval' => 'month',
            'stripe_price_id' => 'price_high',
            'stakes_cap' => 'NL500',
            'features' => "Unlimited conversions\nPriority support",
            'is_visible' => '0',
            'is_active' => '1',
            'is_highlighted' => '1',
            'sort_order' => '5',
        ]);

        $response->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('slug', 'high-stakes')->firstOrFail();
        $this->assertSame('High Stakes', $plan->name);
        $this->assertSame('USD', $plan->currency);
        $this->assertSame('NL500', $plan->stakes_cap);
        $this->assertSame('Covers up to NL500', $plan->stakesText());
        $this->assertFalse($plan->is_visible);
        $this->assertTrue($plan->is_highlighted);
        $this->assertSame(['Unlimited conversions', 'Priority support'], $plan->featureList());
    }

    #[Test]
    public function an_admin_can_update_and_delete_a_package(): void
    {
        $plan = Plan::factory()->create(['name' => 'Old', 'price' => 9]);

        $this->actingAs($this->admin())->put("/admin/plans/{$plan->slug}", [
            'name' => 'New Name',
            'slug' => $plan->slug,
            'price' => '12.50',
            'currency' => 'USD',
            'interval' => $plan->interval,
            'stripe_price_id' => 'price_updated',
            'is_visible' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('admin.plans.index'));

        $this->assertSame('New Name', $plan->fresh()->name);
        $this->assertSame('12.50', $plan->fresh()->price);

        $this->actingAs($this->admin())->delete("/admin/plans/{$plan->slug}")
            ->assertRedirect(route('admin.plans.index'));
        $this->assertModelMissing($plan);
    }

    #[Test]
    public function a_hidden_but_active_package_is_still_subscribable_by_its_slug(): void
    {
        // Route resolves the model even though it is not listed anywhere.
        Plan::factory()->hidden()->create(['slug' => 'unlisted', 'stripe_price_id' => null]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        // No Stripe price set -> friendly error rather than 404 (proves the route matched).
        $this->actingAs($user)->post('/subscribe/unlisted')
            ->assertSessionHasErrors('plan');
    }

    #[Test]
    public function the_direct_subscribe_link_also_works_as_a_plain_get(): void
    {
        // A hidden package's "/subscribe/<slug>" link is meant to be shared and
        // just clicked -> it must open with GET, not only respond to POST.
        Plan::factory()->create([
            'slug' => 'get-checkout', 'price' => 0, 'stripe_price_id' => null,
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/subscribe/get-checkout')
            ->assertRedirect(route('subscription.success'));
    }

    #[Test]
    public function an_inactive_package_slug_404s_on_checkout(): void
    {
        Plan::factory()->inactive()->create(['slug' => 'retired']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/subscribe/retired')->assertNotFound();
    }

    #[Test]
    public function a_zero_dollar_package_can_be_created_without_a_stripe_id(): void
    {
        $this->actingAs($this->admin())->post('/admin/plans', [
            'name' => 'Free Starter',
            'slug' => 'free-starter',
            'price' => '0',
            'currency' => 'USD',
            'interval' => 'month',
            'is_visible' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('admin.plans.index'));

        $plan = Plan::where('slug', 'free-starter')->firstOrFail();
        $this->assertTrue($plan->isFree());
        $this->assertSame('Free', $plan->priceLabel());
    }

    #[Test]
    public function a_paid_package_still_requires_a_stripe_id(): void
    {
        $this->actingAs($this->admin())->post('/admin/plans', [
            'name' => 'Paid No Price',
            'slug' => 'paid-no-price',
            'price' => '15',
            'currency' => 'USD',
            'interval' => 'month',
            'is_visible' => '1',
            'is_active' => '1',
        ])->assertSessionHasErrors('stripe_price_id');
    }

    #[Test]
    public function subscribing_to_a_free_package_grants_access_without_stripe(): void
    {
        Plan::factory()->create([
            'slug' => 'free', 'price' => 0, 'stripe_price_id' => null,
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->post('/subscribe/free')
            ->assertRedirect(route('subscription.success'));

        $user = $user->fresh();
        $this->assertTrue($user->subscribed('default'));
        $this->assertNull($user->stripeId());

        // ...and the converter is now reachable.
        $this->actingAs($user)->get('/convert')->assertOk();
    }
}
