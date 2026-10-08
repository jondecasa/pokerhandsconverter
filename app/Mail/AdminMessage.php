<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A one-to-one message an admin writes to a user, wrapped in the brand template. */
class AdminMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $heading,
        public string $body,
        public bool $withButton = true,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading,
            replyTo: [new Address(config('pokerhandsconverter.contact_email'), config('app.name'))],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-message',
            text: 'emails.admin-message-text',
            with: ['paragraphs' => $this->paragraphs(), 'buttonUrl' => route('pricing')],
        );
    }

    /**
     * The body as escaped HTML paragraphs: a blank line starts a new paragraph,
     * a single line break stays a line break, and bare URLs become links.
     *
     * @return list<string>
     */
    public function paragraphs(): array
    {
        $blocks = preg_split('/\R{2,}/', trim($this->body)) ?: [];

        return array_values(array_map(
            fn (string $block) => nl2br($this->linkify(e(trim($block))), false),
            array_filter($blocks, fn (string $block) => trim($block) !== ''),
        ));
    }

    /** Link http(s) URLs in already-escaped text; trailing punctuation stays outside the link. */
    private function linkify(string $escaped): string
    {
        return (string) preg_replace_callback(
            '~https?://(?:(?!&quot;|&#039;|&lt;|&gt;)[^\s<])+~i',
            function (array $match): string {
                $url = rtrim($match[0], '.,;:!?)');
                $tail = substr($match[0], strlen($url));

                return '<a href="'.$url.'" style="color:#4f46e5; text-decoration:underline;">'.$url.'</a>'.$tail;
            },
            $escaped,
        );
    }
}
