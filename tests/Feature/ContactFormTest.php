<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    #[Test]
    public function the_contact_page_renders(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('Contact us')
            ->assertSee(config('pokerhandsconverter.contact_email'));
    }

    #[Test]
    public function a_valid_message_is_emailed_to_the_contact_address(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Import problem',
            'message' => 'One hand will not import into PT4, can you help?',
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) {
            return $mail->hasTo(config('pokerhandsconverter.contact_email'))
                && $mail->hasReplyTo('jane@example.com')
                && $mail->senderName === 'Jane Doe';
        });
    }

    #[Test]
    public function it_validates_the_input(): void
    {
        Mail::fake();

        $this->post('/contact', ['name' => '', 'email' => 'nope', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_filled_honeypot_is_rejected(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Spam Bot',
            'email' => 'bot@example.com',
            'message' => 'Buy cheap things at example dot com right now',
            'company' => 'AcmeSpam LLC',
        ])->assertSessionHasErrors('company');

        Mail::assertNothingSent();
    }
}
