<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use App\Poker\CoinPokerConverter;
use App\Poker\ConverterOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConverterController extends Controller
{
    public function create(Request $request): View
    {
        return view('converter.create', [
            'recent' => $request->user()->conversions()->latest()->take(10)->get(),
            'maxUploadKb' => config('pokercoinverter.max_upload_kb'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('pokercoinverter.max_upload_kb');

        $validated = $request->validate([
            // Trackers export plain-text ".txt"; we accept any file within the
            // size limit and then verify it is text hand-history content below,
            // because browsers/OSes report inconsistent MIME types for .txt.
            'file' => ['required', 'file', 'max:'.$maxKb],
            'timezone_mode' => ['nullable', 'in:dual,et,keep'],
        ]);

        $uploaded = $validated['file'];
        $raw = file_get_contents($uploaded->getRealPath()) ?: '';

        if (trim($raw) === '') {
            return back()->withErrors(['file' => 'That file looks empty.']);
        }

        if (str_contains($raw, "\0")) {
            return back()->withErrors(['file' => 'That looks like a binary file, not a text hand history.']);
        }

        if (! Str::contains($raw, 'Hand #')) {
            return back()->withErrors(['file' => 'This does not look like a CoinPoker hand-history file (no "Hand #" lines found).']);
        }

        $options = ConverterOptions::fromConfig(array_filter([
            'timezone_mode' => $validated['timezone_mode'] ?? null,
        ]));

        $result = (new CoinPokerConverter($options))->convert($raw);

        if ($result->handCount === 0) {
            return back()->withErrors(['file' => 'No hands could be parsed from that file.']);
        }

        $path = 'conversions/'.$request->user()->id.'/'.Str::uuid()->toString().'.txt';
        Storage::disk('local')->put($path, $result->output);

        $conversion = $request->user()->conversions()->create([
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

        return redirect()
            ->route('conversions.show', $conversion)
            ->with('status', "Converted {$result->handCount} hand(s).");
    }

    public function show(Request $request, Conversion $conversion): View
    {
        $this->authorizeOwner($request, $conversion);

        return view('converter.show', [
            'conversion' => $conversion,
            'preview' => $conversion->outputExists()
                ? Str::of(Storage::disk('local')->get($conversion->output_path))->limit(6000)
                : null,
        ]);
    }

    public function download(Request $request, Conversion $conversion): StreamedResponse
    {
        $this->authorizeOwner($request, $conversion);

        abort_unless($conversion->outputExists(), 404);

        return Storage::disk('local')->download(
            $conversion->output_path,
            $conversion->downloadName(),
            ['Content-Type' => 'text/plain']
        );
    }

    private function authorizeOwner(Request $request, Conversion $conversion): void
    {
        abort_unless($conversion->user_id === $request->user()->id, 403);
    }
}
