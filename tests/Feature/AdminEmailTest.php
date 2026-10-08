<?php

namespace Tests\Feature;

use App\Mail\AdminMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'to' => 'former@example.com',
            'title' => 'We miss you',
            'message' => "Hi there,\n\nCome back and try again.",
            'button' => '1',
        ], $overrides);
    }

    #[Test]
    public function guests_and_non_admins_cannot_reach_the_email_form_or_send(): void
    {
        $this->get('/admin/emails/create')->assertRedirect('/login');
        $this->post('/admin/emails', $this->payload())->assertRedirect('/login');

        $user = User::factory()->create(['email_verified_at' => now()]);
        Mail::fake();

        $this->actingAs($user)->get('/admin/emails/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/emails', $this->payload())->assertForbidden();
        $this->actingAs($user)->post('/admin/emails/preview', $this->payload())->assertForbidden();

        Mail::assertNothingSent();
    }

    #[Test]
    public function an_admin_sees_the_form_and_the_recipient_can_be_prefilled(): void
    {
        $this->actingAs($this->admin())->get('/admin/emails/create?to=someone@example.com')
            ->assertOk()
            ->assertSee('Send email')
            ->assertSee('value="someone@example.com"', false);
    }

    #[Test]
    public function an_admin_sends_the_message_to_the_chosen_address(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post('/admin/emails', $this->payload())
            ->assertRedirect('/admin/emails/create')
            ->assertSessionHas('status', 'Email sent to former@example.com.');

        Mail::assertSent(AdminMessage::class, function (AdminMessage $mail) {
            return $mail->hasTo('former@example.com')
                && $mail->envelope()->subject === 'We miss you'
                && $mail->hasReplyTo(config('pokerhandsconverter.contact_email'));
        });
    }

    #[Test]
    public function the_email_uses_the_brand_template_with_title_text_and_button(): void
    {
        $mail = new AdminMessage('We miss you', "Hi there,\n\nCome back soon.", true);
        $html = $mail->render();

        $this->assertStringContainsString('PokerHands', $html);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('We miss you', $html);
        $this->assertStringContainsString('Come back soon.', $html);
        $this->assertStringContainsString('See the plans', $html);
        $this->assertStringContainsString(route('pricing'), $html);
        $this->assertStringContainsString("if you'd rather not hear from us again", $html);
    }

    #[Test]
    public function the_button_can_be_left_out(): void
    {
        $html = (new AdminMessage('Hello', 'A message with no button at all.', false))->render();

        $this->assertStringNotContainsString('See the plans', $html);
    }

    #[Test]
    public function blank_lines_make_paragraphs_and_urls_become_links(): void
    {
        $mail = new AdminMessage('Hello', "First line\nsecond line\n\nSee https://pokerhandsconverter.com/pricing.", false);

        $paragraphs = $mail->paragraphs();

        $this->assertCount(2, $paragraphs);
        $this->assertSame("First line<br>\nsecond line", $paragraphs[0]);
        $this->assertStringContainsString('<a href="https://pokerhandsconverter.com/pricing"', $paragraphs[1]);
        $this->assertStringEndsWith('</a>.', $paragraphs[1]);
    }

    #[Test]
    public function html_in_the_message_is_escaped(): void
    {
        $html = (new AdminMessage('Hi <b>x</b>', '<script>alert(1)</script> plus "quotes"', false))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
    }

    #[Test]
    public function the_plain_text_version_has_no_html_entities(): void
    {
        $mail = new AdminMessage("It's here", "Don't miss it & more.", true);

        $text = view('emails.admin-message-text', [
            'heading' => $mail->heading, 'body' => $mail->body, 'withButton' => true, 'buttonUrl' => route('pricing'),
        ])->render();

        $this->assertStringContainsString("It's here", $text);
        $this->assertStringContainsString("Don't miss it & more.", $text);
        $this->assertStringNotContainsString('&#039;', $text);
        $this->assertStringContainsString(route('pricing'), $text);
    }

    #[Test]
    public function the_form_is_validated_and_nothing_is_sent_when_it_fails(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/emails', $this->payload(['to' => 'not-an-email']))->assertSessionHasErrors('to');
        $this->actingAs($admin)->post('/admin/emails', $this->payload(['title' => '']))->assertSessionHasErrors('title');
        $this->actingAs($admin)->post('/admin/emails', $this->payload(['title' => "Two\nlines"]))->assertSessionHasErrors('title');
        $this->actingAs($admin)->post('/admin/emails', $this->payload(['message' => 'short']))->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_mail_failure_is_reported_instead_of_crashing(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP down'));

        $this->actingAs($this->admin())->post('/admin/emails', $this->payload())
            ->assertSessionHasErrors('to')
            ->assertSessionDoesntHaveErrors(['title', 'message']);
    }

    #[Test]
    public function the_preview_renders_the_template_without_sending(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->postJson('/admin/emails/preview', ['title' => 'Preview me', 'message' => 'A preview body here.', 'button' => '1'])
            ->assertOk()
            ->assertSee('Preview me')
            ->assertSee('A preview body here.')
            ->assertSee('See the plans');

        Mail::assertNothingSent();
    }

    #[Test]
    public function the_preview_rejects_an_incomplete_form(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/admin/emails/preview', ['title' => '', 'message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'message']);
    }

    #[Test]
    public function cancelled_subscribers_get_an_email_link_in_the_subscribers_table(): void
    {
        $active = User::factory()->create(['email' => 'active@example.com']);
        $gone = User::factory()->create(['email' => 'gone@example.com']);

        Subscription::factory()->for($active, 'user')
            ->create(['type' => 'default', 'stripe_price' => 'price_x', 'stripe_status' => 'active']);
        Subscription::factory()->for($gone, 'user')
            ->create(['type' => 'default', 'stripe_price' => 'price_x', 'stripe_status' => 'canceled', 'ends_at' => now()->subDay()]);

        $html = $this->actingAs($this->admin())->get('/admin/subscribers')->assertOk()->getContent();

        $this->assertStringContainsString('emails/create?to=gone%40example.com', $html);
        $this->assertStringNotContainsString('to=active%40example.com', $html);
    }
}
