<?php

namespace Tests\Feature;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RangesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function subscribedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $subscription = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_'.uniqid(),
            'stripe_product' => 'prod_test',
            'stripe_price' => 'price_monthly',
            'quantity' => 1,
        ]);

        return $user;
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get('/ranges')->assertRedirect('/login');
    }

    #[Test]
    public function non_subscribed_users_are_sent_to_the_plan_picker(): void
    {
        $study = RangeStudy::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/ranges')->assertRedirect(route('subscription.plans'));
        $this->actingAs($user)->get(route('ranges.show', $study))->assertRedirect(route('subscription.plans'));
    }

    #[Test]
    public function a_subscriber_can_see_the_study_list(): void
    {
        RangeStudy::factory()->create(['name' => '6MAX 100BB']);

        $this->actingAs($this->subscribedUser())->get('/ranges')
            ->assertOk()
            ->assertSee('6MAX 100BB');
    }

    #[Test]
    public function a_subscriber_can_open_a_study_and_see_its_default_scenario(): void
    {
        $study = RangeStudy::factory()->create(['name' => '6MAX 100BB']);
        RangeScenario::factory()->for($study, 'study')->create([
            'group_label' => 'Sin oposición',
            'row_label' => 'EP',
            'button_label' => '100BB',
            'is_default' => true,
            'legend' => [['key' => 'raise', 'label' => 'Raise', 'color' => '#22c55e']],
            'combos' => ['AA' => 'raise'],
        ]);

        $html = $this->actingAs($this->subscribedUser())->get(route('ranges.show', $study))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sin oposici', $html);

        preg_match("/scenarios: JSON\.parse\('(.+?)'\)/", $html, $matches);
        $scenarios = json_decode(json_decode('"'.$matches[1].'"'), true);

        $this->assertSame('AA', $scenarios[0]['grid'][0]['hand']);
        $this->assertSame('#22c55e', $scenarios[0]['grid'][0]['color']);
    }
}
