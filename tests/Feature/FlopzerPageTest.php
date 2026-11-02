<?php

namespace Tests\Feature;

use App\Models\RangeScenario;
use App\Models\RangeStudy;
use App\Models\User;
use App\Poker\FlopzerPresets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FlopzerPageTest extends TestCase
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
        $this->get('/flopzer')->assertRedirect('/login');
    }

    #[Test]
    public function non_subscribed_users_are_sent_to_the_plan_picker(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/flopzer')->assertRedirect(route('subscription.plans'));
    }

    #[Test]
    public function subscribers_get_the_analyser_and_a_menu_entry(): void
    {
        $this->actingAs($this->subscribedUser())->get('/flopzer')
            ->assertOk()
            ->assertSee('x-data="flopzer(', false)
            ->assertSee('Your range')
            ->assertSee('Opponent range')
            ->assertSee('Statistics')
            ->assertSee(route('flopzer.index'), false);
    }

    #[Test]
    public function every_chart_of_the_ranges_section_is_offered_as_presets(): void
    {
        $study = RangeStudy::factory()->create(['name' => 'Cash 6-max']);
        RangeScenario::factory()->for($study, 'study')->create([
            'group_label' => 'OR', 'row_label' => 'CO', 'button_label' => '100BB',
            'legend' => [['key' => 'or', 'label' => 'OR', 'color' => '#22c55e'], ['key' => 'or_ext', 'label' => 'OR ampliación', 'color' => '#86efac']],
            'combos' => ['AA' => 'or', 'AKs' => 'or', 'T9s' => 'or_ext', '72o' => null],
        ]);
        RangeScenario::factory()->for($study, 'study')->create([
            'group_label' => 'vs OR', 'row_label' => 'BB', 'button_label' => 'vs CO',
            'legend' => [
                ['key' => 'fold', 'label' => 'Fold', 'color' => '#3b82f6'],
                ['key' => 'raise', 'label' => 'Raise', 'color' => '#ef4444'],
                ['key' => 'call', 'label' => 'Call', 'color' => '#22c55e'],
            ],
            'combos' => ['QQ' => 'raise', '98s' => 'call', '76s' => 'call', '32o' => 'fold'],
        ]);

        $presets = FlopzerPresets::all();

        $this->assertCount(1, $presets);
        $this->assertSame('Cash 6-max', $presets[0]['name']);
        $this->assertSame(['OR', 'vs OR'], array_column($presets[0]['groups'], 'label'));

        $open = $presets[0]['groups'][0]['charts'][0];
        $this->assertSame(['CO', '100BB'], [$open['position'], $open['stack']]);
        $this->assertSame(['OR', 'OR + ampliación'], array_column($open['options'], 'label'));   // the extension is not offered alone
        $this->assertSame('AA, AKs', $open['options'][0]['text']);
        $this->assertSame('AA, AKs, T9s', $open['options'][1]['text']);

        $defend = $presets[0]['groups'][1]['charts'][0];
        $this->assertSame(['BB', 'vs CO'], [$defend['position'], $defend['stack']]);
        $this->assertSame(['Raise', 'Call', 'Raise + Call'], array_column($defend['options'], 'label'));
        $this->assertSame('QQ', $defend['options'][0]['text']);
        $this->assertSame('98s, 76s', $defend['options'][1]['text']);
        $this->assertSame([1, 2, 3], array_column($defend['options'], 'hands'));              // fold is never an option

        $this->actingAs($this->subscribedUser())->get('/flopzer')
            ->assertOk()
            ->assertSee('Load a range')
            ->assertSee('Cash 6-max')
            ->assertSee('vs OR');
    }

    #[Test]
    public function a_chart_with_more_than_two_actions_gets_an_everything_but_fold_option(): void
    {
        $study = RangeStudy::factory()->create();
        RangeScenario::factory()->for($study, 'study')->create([
            'group_label' => 'vs 3bet', 'row_label' => 'CO', 'button_label' => 'vs BTN',
            'legend' => [
                ['key' => 'fold', 'label' => 'Fold', 'color' => '#3b82f6'],
                ['key' => 'call', 'label' => 'Call', 'color' => '#22c55e'],
                ['key' => '4bf', 'label' => '4bet → Fold', 'color' => '#ef4444'],
                ['key' => '4bc', 'label' => '4bet → Call', 'color' => '#7f1d1d'],
            ],
            'combos' => ['JJ' => 'call', 'AKs' => '4bc', 'A5s' => '4bf', '72o' => 'fold'],
        ]);

        $options = FlopzerPresets::all()[0]['groups'][0]['charts'][0]['options'];

        $this->assertSame(['Call', '4bet → Fold', '4bet → Call', 'Everything but fold'], array_column($options, 'label'));
        $this->assertSame('JJ, AKs, A5s', $options[3]['text']);
    }

    #[Test]
    public function without_ranges_there_is_nothing_to_load(): void
    {
        $this->assertSame([], FlopzerPresets::all());
    }
}
