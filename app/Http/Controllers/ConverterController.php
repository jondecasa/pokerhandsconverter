<?php

namespace App\Http\Controllers;

use App\Mail\ConversionFeedback;
use App\Models\Conversion;
use App\Models\ConversionFailure;
use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use App\Support\UploadLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConverterController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();

        return view('converter.create', [
            'recent' => $user->conversions()->latest()->take(10)->get(),
            // What the server really takes (PHP's limits can be below the app's), so the page never promises more.
            'maxUploadKb' => UploadLimits::effectiveKb(),
            'stakesCap' => $user->currentPlan()?->stakes_cap,
            'heroName' => $user->heroName(),
            'includeBombPots' => (bool) $user->pref('include_bomb_pots', true),
            'includeSplashPots' => (bool) $user->pref('include_splash_pots', true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('pokerhandsconverter.max_upload_kb');

        try {
            $validated = $request->validate([
                // Trackers export plain-text ".txt"; we accept any file within the
                // size limit and then verify it is text hand-history content below,
                // because browsers/OSes report inconsistent MIME types for .txt.
                'file' => ['required', 'file', 'max:'.$maxKb],
                'timezone_mode' => ['nullable', 'in:dual,et,keep'],
                'include_bomb_pots' => ['nullable', 'boolean'],
                'include_splash_pots' => ['nullable', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            // The file never got past validation (too big, upload error, missing): keep a trace of it.
            $this->recordFailure(
                $request,
                $request->file('file'),
                $e->validator->errors()->has('file') ? 'upload' : 'validation',
                implode(' ', $e->validator->errors()->all()),
                $this->uploadDetails($request)
            );

            throw $e;
        }

        $includeBombPots = $request->boolean('include_bomb_pots');
        $includeSplashPots = $request->boolean('include_splash_pots');

        $uploaded = $validated['file'];
        $raw = file_get_contents($uploaded->getRealPath()) ?: '';

        if (trim($raw) === '') {
            return $this->reject($request, $uploaded, 'empty', 'That file looks empty.');
        }

        if (str_contains($raw, "\0")) {
            // A text file saved as UTF-16 (Windows Notepad's "Unicode") is full of NUL bytes too.
            $utf16 = str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF");

            return $this->reject($request, $uploaded, 'binary', 'That looks like a binary file, not a text hand history.',
                $utf16 ? 'Starts with a UTF-16 byte-order mark: the file was probably saved as UTF-16 instead of UTF-8.' : 'Contains NUL bytes.');
        }

        if (! Str::contains($raw, 'Hand #')) {
            return $this->reject($request, $uploaded, 'not_coinpoker', 'This does not look like a CoinPoker hand-history file (no "Hand #" lines found).',
                'First line: '.$this->firstLine($raw));
        }

        $user = $request->user();

        try {
            // Persist the include-bomb / include-splash choices as preferences.
            $user->update([
                'preferences' => array_merge($user->preferences ?? [], [
                    'include_bomb_pots' => $includeBombPots,
                    'include_splash_pots' => $includeSplashPots,
                ]),
            ]);

            $options = ConverterOptions::fromConfig(array_filter([
                'timezone_mode' => $validated['timezone_mode'] ?? null,
                'hero_name' => $user->heroName() !== 'Hero' ? $user->heroName() : null,
            ]) + [
                'include_bomb_pots' => $includeBombPots,
                'include_splash_pots' => $includeSplashPots,
            ]);

            $result = (new CoinPokerConverter($options))->convert($raw);

            if ($result->handCount === 0) {
                $excluded = $result->excludedBombPots + $result->excludedSplashPots;

                return $this->reject($request, $uploaded, 'no_hands', $excluded > 0
                    ? 'Every hand in that file was a bomb pot or splash pot and you chose to exclude those.'
                    : 'No hands could be parsed from that file.',
                    implode("\n", array_filter([
                        'First line: '.$this->firstLine($raw),
                        $excluded > 0 ? "Excluded bomb/splash pots: {$excluded}" : null,
                        $result->warnings ? 'Warnings: '.collect($result->warnings)->take(5)
                            ->map(fn ($w) => ($w['hand'] ? 'hand '.$w['hand'].': ' : '').$w['message'])->implode(' | ') : null,
                    ])));
            }

            // Stakes gate: the file may not exceed the package's stake cap.
            $cap = $user->currentPlan()?->stakesCapBigBlind();
            $maxBb = $result->maxCashBigBlind();
            if ($cap !== null && $maxBb !== null && $maxBb > $cap + 1e-9) {
                $capLabel = $user->currentPlan()->stakes_cap;
                $fileLabel = $result->maxCashStakeLevel();

                return $this->reject($request, $uploaded, 'stakes_cap',
                    "This file contains {$fileLabel} hands but your package covers up to {$capLabel}. Upgrade your package to convert it.",
                    "File stake level {$fileLabel}, package cap {$capLabel}, {$result->handCount} hand(s).");
            }

            $path = 'conversions/'.$user->id.'/'.Str::uuid()->toString().'.txt';
            Storage::disk('local')->put($path, $result->output);

            $conversion = $user->conversions()->create([
                'original_filename' => $uploaded->getClientOriginalName(),
                'output_path' => $path,
                'hand_count' => $result->handCount,
                'splash_pots' => $result->splashPotCount(),
                'bomb_pots' => $result->bombPotCount(),
                'warning_count' => count($result->warnings),
                'input_bytes' => strlen($raw),
                'format_breakdown' => $result->formatBreakdown(),
                'warnings' => array_slice($result->warnings, 0, 50),
            ]);
        } catch (\Throwable $e) {
            // Also goes to storage/logs/laravel.log; the user gets a message instead of a bare 500.
            report($e);

            return $this->reject($request, $uploaded, 'exception',
                'Something went wrong while converting that file. It has been logged and we will look into it.',
                $this->exceptionDetails($e).($e->getPrevious() ? "\nCaused by: ".$this->exceptionDetails($e->getPrevious()) : '')
                ."\nFirst line: ".$this->firstLine($raw));
        }

        $status = "Converted {$result->handCount} hand(s).";
        $skipped = [];
        if ($result->excludedBombPots > 0) {
            $skipped[] = "{$result->excludedBombPots} bomb pot(s)";
        }
        if ($result->excludedSplashPots > 0) {
            $skipped[] = "{$result->excludedSplashPots} splash pot(s)";
        }
        if ($skipped) {
            $status .= ' Excluded '.implode(' and ', $skipped).'.';
        }

        return redirect()->route('conversions.show', $conversion)->with('status', $status);
    }

    public function show(Request $request, Conversion $conversion): View
    {
        $this->authorizeViewer($request, $conversion);

        return view('converter.show', [
            'conversion' => $conversion,
            'preview' => $conversion->outputExists()
                ? Str::of(Storage::disk('local')->get($conversion->output_path))->limit(6000)
                : null,
        ]);
    }

    public function download(Request $request, Conversion $conversion): StreamedResponse
    {
        $this->authorizeViewer($request, $conversion);

        abort_unless($conversion->outputExists(), 404);

        return Storage::disk('local')->download(
            $conversion->output_path,
            $conversion->downloadName(),
            ['Content-Type' => 'text/plain']
        );
    }

    public function destroy(Request $request, Conversion $conversion): RedirectResponse
    {
        $this->authorizeOwner($request, $conversion);

        $name = $conversion->original_filename;

        // The model's "deleting" hook removes the stored output file too.
        $conversion->delete();

        return redirect()
            ->route('convert.create')
            ->with('status', "Deleted the conversion of \"{$name}\" and its converted file.");
    }

    /** A user reports a tracker import problem for their own conversion — emails the admin. */
    public function feedback(Request $request, Conversion $conversion): RedirectResponse
    {
        $this->authorizeOwner($request, $conversion);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        Mail::to(config('pokerhandsconverter.contact_email'))->send(
            new ConversionFeedback($request->user(), $conversion, $data['message'])
        );

        return back()->with('status', "Thanks — we'll take a look at \"{$conversion->original_filename}\".");
    }

    /** Keep the upload attempt in the admin log, then send the user back with the reason. */
    private function reject(Request $request, UploadedFile $uploaded, string $reason, string $message, ?string $details = null): RedirectResponse
    {
        $this->recordFailure($request, $uploaded, $reason, $message, $details);

        return back()->withErrors(['file' => $message]);
    }

    /** Logging must never be the thing that breaks an upload, so a failure to write the row is only reported. */
    private function recordFailure(Request $request, ?UploadedFile $file, string $reason, string $message, ?string $details = null): void
    {
        try {
            ConversionFailure::create([
                'user_id' => $request->user()?->id,
                'original_filename' => $file ? Str::limit($file->getClientOriginalName(), 255, '') : null,
                'file_size' => $this->fileSize($file),
                'reason' => $reason,
                'message' => Str::limit($message, 1000, ''),
                'details' => $details !== null ? Str::limit($details, 4000, '…') : null,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function fileSize(?UploadedFile $file): ?int
    {
        try {
            return $file ? (int) $file->getSize() : null;
        } catch (\Throwable) {
            return null; // a failed upload has no temp file to measure
        }
    }

    /** What PHP/the web server did with the upload: the usual culprits when no file reaches the app. */
    private function uploadDetails(Request $request): string
    {
        $file = $request->file('file');
        $lines = [];
        if ($file === null) {
            $lines[] = 'No file reached the app.';
        } elseif (! $file->isValid()) {
            $lines[] = 'Upload error '.$file->getError().': '.$file->getErrorMessage();
        }
        $lines[] = 'Content-Length: '.($request->server('CONTENT_LENGTH') ?? 'n/a');
        $lines[] = 'PHP upload_max_filesize='.ini_get('upload_max_filesize').', post_max_size='.ini_get('post_max_size')
            .', max_execution_time='.ini_get('max_execution_time').', memory_limit='.ini_get('memory_limit');
        $lines[] = 'App limit: '.config('pokerhandsconverter.max_upload_kb').' KB';

        return implode("\n", $lines);
    }

    private function firstLine(string $raw): string
    {
        foreach (preg_split('/\R/', $raw) as $line) {
            if (trim($line) !== '') {
                return Str::limit(trim($line), 160, '…');
            }
        }

        return '(empty)';
    }

    private function exceptionDetails(\Throwable $e): string
    {
        $trace = collect(explode("\n", $e->getTraceAsString()))->take(8)->implode("\n");

        return get_class($e).': '.$e->getMessage().' at '.basename($e->getFile()).':'.$e->getLine()."\n".$trace;
    }

    private function authorizeOwner(Request $request, Conversion $conversion): void
    {
        abort_unless($conversion->user_id === $request->user()->id, 403);
    }

    /** Same as authorizeOwner, but lets an admin open a conversion someone reported for debugging. */
    private function authorizeViewer(Request $request, Conversion $conversion): void
    {
        abort_unless(
            $conversion->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );
    }
}
