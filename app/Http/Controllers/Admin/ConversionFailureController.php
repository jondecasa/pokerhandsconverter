<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConversionFailure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversionFailureController extends Controller
{
    public function index(Request $request): View
    {
        [$search, $reason] = $this->filters($request);

        return view('admin.failures.index', [
            'failures' => $this->filtered($search, $reason)->with('user:id,name,email')->latest('id')->paginate(25)->withQueryString(),
            'search' => $search,
            'reason' => $reason,
            'total' => ConversionFailure::count(),
            'counts' => ConversionFailure::query()->selectRaw('reason, count(*) as total')->groupBy('reason')->pluck('total', 'reason'),
        ]);
    }

    public function destroy(ConversionFailure $failure): RedirectResponse
    {
        $failure->delete();

        return back()->with('status', 'Deleted that log entry.');
    }

    /**
     * Deletes the ticked entries (?ids[]=1&ids[]=2) or, with ?all=1, everything that matches the current filter.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        [$search, $reason] = $this->filters($request);
        $back = redirect()->route('admin.failures.index', array_filter(['q' => $search, 'reason' => $reason]));

        if ($request->boolean('all')) {
            $deleted = $this->filtered($search, $reason)->delete();
        } else {
            $ids = collect((array) $request->input('ids', []))->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique();
            if ($ids->isEmpty()) {
                return $back->with('error', 'Select at least one entry to delete.');
            }
            $deleted = ConversionFailure::whereIn('id', $ids)->delete();
        }

        return $back->with('status', 'Deleted '.$deleted.' log '.($deleted === 1 ? 'entry' : 'entries').'.');
    }

    /** @return array{0: string, 1: string} the search text and the reason code (both '' when not filtering) */
    private function filters(Request $request): array
    {
        $search = trim((string) $request->input('q'));
        $reason = (string) $request->input('reason');

        return [$search, array_key_exists($reason, ConversionFailure::REASONS) ? $reason : ''];
    }

    private function filtered(string $search, string $reason): Builder
    {
        return ConversionFailure::query()
            ->when($reason !== '', fn (Builder $query) => $query->where('reason', $reason))
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('original_filename', 'like', $like)
                    ->orWhere('message', 'like', $like)
                    ->orWhere('details', 'like', $like)
                    ->orWhere('ip', 'like', $like)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', $like)->orWhere('name', 'like', $like)));
            });
    }
}
