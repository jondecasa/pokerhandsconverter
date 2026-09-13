<?php

namespace Tests\Feature;

use App\Mail\ConversionFeedback;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
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
    public function subscribers_without_a_plan_are_sent_to_the_plan_picker(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get('/convert')->assertRedirect(route('subscription.plans'));
    }

    #[Test]
    public function a_subscriber_can_open_the_converter(): void
    {
        $this->actingAs($this->subscribedUser())->get('/convert')->assertOk();
    }

    #[Test]
    public function the_in_app_plan_picker_renders_the_visible_packages(): void
    {
        Plan::factory()->create(['name' => 'Mid Stakes', 'slug' => 'mid']);
        Plan::factory()->hidden()->create(['name' => 'Hidden VIP', 'slug' => 'vip']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('subscription.plans'))
            ->assertOk()
            ->assertSee('Mid Stakes')
            ->assertDontSee('Hidden VIP');
    }

    #[Test]
    public function a_subscriber_can_convert_a_file_and_download_the_result(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();

        $response = $this->actingAs($user)->post('/convert', [
            'file' => $this->fixtureUpload(),
            'timezone_mode' => 'dual',
        ]);

        $conversion = $user->conversions()->firstOrFail();
        $response->assertRedirect(route('conversions.show', $conversion));

        $this->assertSame(1, $conversion->hand_count);
        Storage::disk('local')->assertExists($conversion->output_path);

        $download = $this->actingAs($user)->get(route('conversions.download', $conversion));
        $download->assertOk();
        $download->assertDownload('coinpoker-cash-pokertracker.txt');
        $this->assertStringContainsString('CoinPoker Hand #130114200045:  Hold\'em No Limit', $download->streamedContent());
        $this->assertStringNotContainsString('PokerStars', $download->streamedContent());
        $this->assertStringNotContainsString('₮', $download->streamedContent());
    }

    #[Test]
    public function the_conversion_url_uses_an_opaque_token_not_the_numeric_id(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();
        $this->actingAs($user)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $user->conversions()->firstOrFail();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{20,30}$/', $conversion->public_id);
        $this->assertStringNotContainsString("/conversions/{$conversion->id}", route('conversions.show', $conversion));
        $this->assertStringContainsString("/conversions/{$conversion->public_id}", route('conversions.show', $conversion));

        $this->actingAs($user)->get(route('conversions.show', $conversion))->assertOk();

        // The old-style "guess the next numeric id" URL no longer resolves.
        $this->actingAs($user)->get("/conversions/{$conversion->id}")->assertNotFound();
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

    #[Test]
    public function the_owner_can_delete_a_conversion_and_its_output_file(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();
        $this->actingAs($user)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $user->conversions()->firstOrFail();
        Storage::disk('local')->assertExists($conversion->output_path);

        $this->actingAs($user)
            ->delete(route('conversions.destroy', $conversion))
            ->assertRedirect(route('convert.create'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('conversions', ['id' => $conversion->id]);
        Storage::disk('local')->assertMissing($conversion->output_path);
    }

    #[Test]
    public function a_user_cannot_delete_another_users_conversion(): void
    {
        Storage::fake('local');
        $owner = $this->subscribedUser();
        $this->actingAs($owner)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $owner->conversions()->firstOrFail();

        $intruder = $this->subscribedUser();
        $this->actingAs($intruder)
            ->delete(route('conversions.destroy', $conversion))
            ->assertForbidden();

        $this->assertDatabaseHas('conversions', ['id' => $conversion->id]);
        Storage::disk('local')->assertExists($conversion->output_path);
    }

    #[Test]
    public function warnings_render_as_a_collapsed_details_block(): void
    {
        Storage::fake('local');
        $user = $this->subscribedUser();
        $this->actingAs($user)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $user->conversions()->firstOrFail();
        $conversion->update(['warnings' => [['hand' => 3, 'message' => 'Run-it-twice hand kept as one hand.']]]);

        $html = $this->actingAs($user)->get(route('conversions.show', $conversion))->getContent();

        $this->assertStringContainsString('<details', $html);
        $this->assertStringNotContainsString('<details open', $html);
        $this->assertStringContainsString('1 warning(s)', $html);
    }

    #[Test]
    public function the_owner_can_email_feedback_about_a_pt4_import_problem(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = $this->subscribedUser();
        $this->actingAs($user)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $user->conversions()->firstOrFail();

        $response = $this->actingAs($user)->post(route('conversions.feedback', $conversion), [
            'message' => 'PokerTracker rejected this with "Invalid pot size" on hand #12345.',
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        Mail::assertSent(ConversionFeedback::class, function (ConversionFeedback $mail) use ($user, $conversion) {
            return $mail->hasTo(config('pokerhandsconverter.contact_email'))
                && $mail->hasReplyTo($user->email)
                && $mail->conversion->is($conversion)
                && str_contains($mail->body, 'Invalid pot size');
        });
    }

    #[Test]
    public function feedback_requires_a_real_message_and_only_the_owner_can_send_it(): void
    {
        Mail::fake();
        Storage::fake('local');
        $owner = $this->subscribedUser();
        $this->actingAs($owner)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $owner->conversions()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('conversions.feedback', $conversion), ['message' => 'too short'])
            ->assertSessionHasErrors('message');

        $intruder = $this->subscribedUser();
        $this->actingAs($intruder)
            ->post(route('conversions.feedback', $conversion), ['message' => 'This is a long enough message.'])
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    #[Test]
    public function an_admin_can_open_a_reported_conversion_but_not_delete_it(): void
    {
        Storage::fake('local');
        $owner = $this->subscribedUser();
        $this->actingAs($owner)->post('/convert', ['file' => $this->fixtureUpload()]);
        $conversion = $owner->conversions()->firstOrFail();

        // Also subscribed: /conversions/* sits behind the "subscribed" middleware
        // for everyone, admins included.
        $admin = $this->subscribedUser();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('conversions.show', $conversion))->assertOk();
        $this->actingAs($admin)->get(route('conversions.download', $conversion))->assertOk();
        $this->actingAs($admin)->delete(route('conversions.destroy', $conversion))->assertForbidden();

        $this->assertDatabaseHas('conversions', ['id' => $conversion->id]);
    }
}
