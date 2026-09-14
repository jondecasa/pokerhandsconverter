<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RangeStudyRequest;
use App\Models\RangeStudy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RangeStudyController extends Controller
{
    public function index(): View
    {
        return view('admin.range-studies.index', [
            'studies' => RangeStudy::ordered()->withCount('scenarios')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.range-studies.create', [
            'study' => new RangeStudy,
        ]);
    }

    public function store(RangeStudyRequest $request): RedirectResponse
    {
        $study = RangeStudy::create($request->studyData());

        return redirect()->route('admin.range-studies.edit', $study)
            ->with('status', "Study \"{$study->name}\" created. Now add its scenarios.");
    }

    public function edit(RangeStudy $rangeStudy): View
    {
        return view('admin.range-studies.edit', [
            'study' => $rangeStudy,
            'navigation' => $rangeStudy->navigation(),
        ]);
    }

    public function update(RangeStudyRequest $request, RangeStudy $rangeStudy): RedirectResponse
    {
        $rangeStudy->update($request->studyData());

        return redirect()->route('admin.range-studies.edit', $rangeStudy)
            ->with('status', "Study \"{$rangeStudy->name}\" updated.");
    }

    public function destroy(RangeStudy $rangeStudy): RedirectResponse
    {
        $name = $rangeStudy->name;
        $rangeStudy->delete();

        return redirect()->route('admin.range-studies.index')
            ->with('status', "Study \"{$name}\" and its scenarios deleted.");
    }
}
