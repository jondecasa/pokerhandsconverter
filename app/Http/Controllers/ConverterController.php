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
        $user = $request->user();

        return view('converter.create', [
            'recent' => $user->conversions()->latest()->take(10)->get(),
            'maxUploadKb' => config('pokercoinverter.max_upload_kb'),
            'stakesCap' => $user->currentPlan()?->stakes_cap,
            'heroName' => $user->heroName(),
            'includeBombPots' => (bool) $user->pref('include_bomb_pots', true),
            'includeSplashPots' => (bool) $user->pref('include_splash_pots', true),
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
            'include_bomb_pots' => ['nullable', 'boolean'],
            'include_splash_pots' => ['nullable', 'boolean'],
        ]);

        $includeBombPots = $request->boolean('include_bomb_pots');
        $includeSplashPots = $request->boolean('include_splash_pots');

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

        $user = $request->user();

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

            return back()->withErrors(['file' => $excluded > 0
                ? 'Every hand in that file was a bomb pot or splash pot and you chose to exclude those.'
                : 'No hands could be parsed from that file.']);
        }

        // Stakes gate: the file may not exceed the package's stake cap.
        $cap = $user->currentPlan()?->stakesCapBigBlind();
        $maxBb = $result->maxCashBigBlind();
        if ($cap !== null && $maxBb !== null && $maxBb > $cap + 1e-9) {
            $capLabel = $user->currentPlan()->stakes_cap;
            $fileLabel = $result->maxCashStakeLevel();

            return back()->withErrors([
                'file' => "This file contains {$fileLabel} hands but your package covers up to {$capLabel}. Upgrade your package to convert it.",
            ]);
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

    private function authorizeOwner(Request $request, Conversion $conversion): void
    {
        abort_unless($conversion->user_id === $request->user()->id, 403);
    }
}
