<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.emails.create', [
            'to' => (string) $request->query('to'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, withRecipient: true);

        try {
            Mail::to($data['to'])->send($this->mailable($data));
        } catch (Throwable $e) {
            report($e);
            Log::warning('Admin email to '.$data['to'].' failed: '.$e->getMessage());

            return back()->withInput()->withErrors(['to' => 'The email could not be sent. Check the mail settings and try again.']);
        }

        return redirect()->route('admin.emails.create')
            ->with('status', "Email sent to {$data['to']}.");
    }

    public function preview(Request $request): Response
    {
        $data = $this->validated($request, withRecipient: false);

        return response($this->mailable($data)->render());
    }

    /** @return array{to?: string, title: string, message: string, button: bool} */
    private function validated(Request $request, bool $withRecipient): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:150', 'regex:/^[^\r\n]+$/'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'button' => ['nullable', 'boolean'],
        ];

        if ($withRecipient) {
            $rules['to'] = ['required', 'email', 'max:190'];
        }

        $data = $request->validate($rules);
        $data['button'] = $request->boolean('button');

        return $data;
    }

    /** @param  array{title: string, message: string, button: bool}  $data */
    private function mailable(array $data): AdminMessage
    {
        return new AdminMessage($data['title'], $data['message'], $data['button']);
    }
}
