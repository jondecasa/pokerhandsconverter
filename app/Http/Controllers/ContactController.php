<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Honeypot: real users leave it empty.
            'company' => ['nullable', 'size:0'],
        ], [
            'company.size' => 'Spam detected.',
        ]);

        Mail::to(config('pokerhandsconverter.contact_email'))->send(new ContactMessage(
            senderName: $data['name'],
            senderEmail: $data['email'],
            subjectLine: $data['subject'] ?: 'No subject',
            body: $data['message'],
        ));

        return back()->with('status', 'Thanks — your message is on its way. We usually reply within a day or two.');
    }
}
