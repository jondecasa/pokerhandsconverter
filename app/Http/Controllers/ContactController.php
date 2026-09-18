<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('marketing.contact');
    }

    public function submit(Request $request): RedirectResponse
    {
        [$messages, $attributes] = $this->validationText();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot: real users leave it empty.
            'company' => ['nullable', 'size:0'],
        ], ['company.size' => __('Spam detected.')] + $messages, $attributes);

        Mail::to(config('pokerhandsconverter.contact_email'))->send(new ContactMessage(
            senderName: $data['name'],
            senderEmail: $data['email'],
            subjectLine: ($data['subject'] ?? null) ?: 'No subject',
            body: $data['message'],
        ));

        return back()->with('status', __('Thanks — your message is on its way. We usually reply within a day or two.'));
    }

    /**
     * Validation wording for the non-default languages. English keeps Laravel's
     * own messages, so only translated pages pass custom ones.
     *
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function validationText(): array
    {
        if (Locales::isDefault()) {
            return [[], []];
        }

        return [
            [
                'required' => __('The :attribute field is required.'),
                'email' => __('The :attribute field must be a valid email address.'),
                'max.string' => __('The :attribute field must not be greater than :max characters.'),
                'min.string' => __('The :attribute field must be at least :min characters.'),
            ],
            [
                'name' => __('Your name'),
                'email' => __('Email'),
                'subject' => __('Subject'),
                'message' => __('Message'),
            ],
        ];
    }
}
