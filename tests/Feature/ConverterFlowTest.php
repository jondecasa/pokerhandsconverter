<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConverterFlowTest extends TestCase
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

    private function fixtureUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'coinpoker-cash.txt',
            file_get_contents(base_path('tests/Fixtures/coinpoker-cash.txt'))
        );
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get('/convert')->assertRedirect('/login');
    }

    #[Test]
    public function subscribers_without_a_plan_are_sent_to_pricing(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/convert')->assertRedirect(route('pricing'));
    }

    #[Test]
    public function a_subscriber_can_open_the_converter(): void
    {
        $this->actingAs($this->subscribedUser())->get('/convert')->assertOk();
    }

    #[Test]
    public function a_subscriber_can_convert_a_file_and_download_the_result(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();

        $response = $this->actingAs($user)->post('/convert', [
            'file' => $this->fixtureUpload(),
            'timezone_mode' => 'relabel',
        ]);

        $conversion = $user->conversions()->firstOrFail();
        $response->assertRedirect(route('conversions.show', $conversion));

        $this->assertSame(2, $conversion->hand_count);
        Storage::disk('local')->assertExists($conversion->output_path);

        $download = $this->actingAs($user)->get(route('conversions.download', $conversion));
        $download->assertOk();
        $download->assertDownload('coinpoker-cash-pokerstars.txt');
        $this->assertStringContainsString('PokerStars Hand #2100000001', $download->streamedContent());
        $this->assertStringNotContainsString('CoinPoker Hand #', $download->streamedContent());
    }

    #[Test]
    public function a_non_text_upload_is_rejected(): void
    {
        $user = $this->subscribedUser();

        $this->actingAs($user)
            ->post('/convert', ['file' => UploadedFile::fake()->create('hand.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
    }

    #[Test]
    public function a_user_cannot_view_another_users_conversion(): void
    {
        Storage::fake('local');
        $owner = $this->subscribedUser();
        $this->actingAs($owner)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $owner->conversions()->firstOrFail();

        $intruder = $this->subscribedUser();
        $this->actingAs($intruder)->get(route('conversions.show', $conversion))->assertForbidden();
        $this->actingAs($intruder)->get(route('conversions.download', $conversion))->assertForbidden();
    }
}
