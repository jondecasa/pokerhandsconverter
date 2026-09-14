<?php

namespace App\Http\Controllers;

use App\Models\RangeStudy;
use Illuminate\View\View;

class RangeController extends Controller
{
    public function index(): View
    {
        return view('ranges.index', [
            'studies' => RangeStudy::ordered()->withCount('scenarios')->get(),
        ]);
    }

    public function show(RangeStudy $rangeStudy): View
    {
        $rangeStudy->load('scenarios');

        return view('ranges.show', [
            'study' => $rangeStudy,
            'navigation' => $rangeStudy->navigation(),
            'scenarios' => $rangeStudy->scenarios,
            'defaultScenario' => $rangeStudy->defaultScenario(),
        ]);
    }
}
