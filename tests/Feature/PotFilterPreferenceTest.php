<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PotFilterPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function subscriber(): User
    {
        $plan = Plan::factory()->create(['stripe_price_id' => 'price_x', 'stakes_cap' => null]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $sub = $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => 'active', 'stripe_price' => 'price_x', 'quantity' => 1,
        ]);
        $sub->items()->create([
            'stripe_id' => 'si_'.uniqid(), 'stripe_product' => 'prod',
            'stripe_price' => 'price_x', 'quantity' => 1,
        ]);

        return $user;
    }

    /** cash hand + splash-pot hand + bomb-pot hand in one file */
    private function mixedUpload(): UploadedFile
    {
        $body = file_get_contents(base_path('tests/Fixtures/coinpoker-cash.txt'))."\n\n"
            .file_get_contents(base_path('tests/Fixtures/coinpoker-splash-pot.txt'))."\n\n"
            .file_get_contents(base_path('tests/Fixtures/coinpoker-bomb-pot.txt'));

        return UploadedFile::fake()->createWithContent('mixed.txt', $body);
    }

    #[Test]
    public function unchecking_the_boxes_excludes_those_pots_and_saves_the_preference(): void
    {
        Storage::fake('local');
        $user = $this->subscriber();

        $this->actingAs($user)->post('/convert', [
            'file' => $this->mixedUpload(),
            'include_bomb_pots' => '0',
            'include_splash_pots' => '0',
        ]);

        $conversion = $user->conversions()->firstOrFail();
        $out = Storage::disk('local')->get($conversion->output_path);

        // the plain cash hand from coinpoker-cash.txt remains
        $this->assertStringContainsString('Hand #130114200045', $out);
        // the mega/splash and bomb hands are gone
        $this->assertStringNotContainsString('Hand #129794100893', $out);
        $this->assertStringNotContainsString('Hand #129794100999', $out);
        $this->assertStringNotContainsString('Hand #129794200777', $out);

        $user->refresh();
        $this->assertFalse($user->pref('include_bomb_pots'));
        $this->assertFalse($user->pref('include_splash_pots'));
    }

    #[Test]
    public function the_saved_preference_pre_checks_the_boxes_on_the_form(): void
    {
        $user = $this->subscriber();
        $user->update(['preferences' => ['include_bomb_pots' => false, 'include_splash_pots' => true]]);

        $html = $this->actingAs($user)->get('/convert')->assertOk()->getContent();

        // splash box checked, bomb box not
        $this->assertMatchesRegularExpression('/name="include_splash_pots" value="1" checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="include_bomb_pots" value="1" checked/', $html);
    }

    #[Test]
    public function keeping_the_boxes_checked_keeps_every_hand(): void
    {
        Storage::fake('local');
        $user = $this->subscriber();

        $this->actingAs($user)->post('/convert', [
            'file' => $this->mixedUpload(),
            'include_bomb_pots' => '1',
            'include_splash_pots' => '1',
        ]);

        $out = Storage::disk('local')->get($user->conversions()->firstOrFail()->output_path);

        $this->assertStringContainsString('Hand #129794100893', $out);
        $this->assertStringContainsString('Hand #129794200777', $out);
    }
}
