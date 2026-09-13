<?php

namespace App\Mail;

use App\Models\Conversion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConversionFeedback extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $reporter,
        public Conversion $conversion,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PT4 import issue: '.$this->conversion->original_filename,
            replyTo: [new Address($this->reporter->email, $this->reporter->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.conversion-feedback');
    }
}
