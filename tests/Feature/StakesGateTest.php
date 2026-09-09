<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StakesGateTest extends TestCase
{
    use RefreshDatabase;

    private function subscriberOnPlan(?string $stakesCap): User
    {
        $plan = Plan::factory()->create([
            'slug' => 'test-plan',
            'stripe_price_id' => 'price_test',
            'stakes_cap' => $stakesCap,
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $sub = $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);
        $sub->items()->create([
            'stripe_id' => 'si_'.uniqid(),
            'stripe_product' => 'prod_test',
            'stripe_price' => $plan->stripe_price_id,
            'quantity' => 1,
        ]);

        return $user;
    }

    private function nl50Upload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'nl50.txt',
            file_get_contents(base_path('samples/coinpoker-nl50-example.txt'))
        );
    }

    #[Test]
    public function an_nl2_package_cannot_convert_an_nl50_file(): void
    {
        Storage::fake('local');
        $user = $this->subscriberOnPlan('NL2');

        $this->actingAs($user)->post('/convert', ['file' => $this->nl50Upload()])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $user->conversions()->count());
    }

    #[Test]
    public function an_nl100_package_can_convert_an_nl50_file(): void
    {
        Storage::fake('local');
        $user = $this->subscriberOnPlan('NL100');

        $this->actingAs($user)->post('/convert', ['file' => $this->nl50Upload()]);

        $this->assertSame(1, $user->conversions()->count());
        $this->assertSame(4, $user->conversions()->first()->hand_count);
    }

    #[Test]
    public function a_package_with_no_stake_cap_converts_anything(): void
    {
        Storage::fake('local');
        $user = $this->subscriberOnPlan(null);

        $this->actingAs($user)->post('/convert', ['file' => $this->nl50Upload()]);

        $this->assertSame(1, $user->conversions()->count());
    }

    #[Test]
    public function the_users_coinpoker_id_replaces_hero_in_the_output(): void
    {
        Storage::fake('local');
        $user = $this->subscriberOnPlan(null);
        $user->update(['coinpoker_id' => 'batu157']);

        $this->actingAs($user)->post('/convert', ['file' => $this->nl50Upload()]);

        $path = $user->conversions()->first()->output_path;
        $out = Storage::disk('local')->get($path);

        $this->assertStringContainsString('Dealt to batu157 [Ah Ks]', $out);
        $this->assertStringNotContainsString('Hero', $out);
    }
}
