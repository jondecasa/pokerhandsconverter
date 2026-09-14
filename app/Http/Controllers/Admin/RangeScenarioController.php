<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RangeScenarioRequest;
use App\Models\RangeScenario;
use App\Models\RangeStudy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RangeScenarioController extends Controller
{
    public function create(RangeStudy $rangeStudy): View
    {
        return view('admin.range-scenarios.create', [
            'study' => $rangeStudy,
            'scenario' => new RangeScenario([
                'legend' => [
                    ['key' => 'raise', 'label' => 'Raise', 'color' => '#22c55e'],
                    ['key' => 'call', 'label' => 'Call', 'color' => '#eab308'],
                    ['key' => 'fold', 'label' => 'Fold', 'color' => '#ef4444'],
                ],
                'combos' => [],
                'stats' => null,
            ]),
        ]);
    }

    public function store(RangeScenarioRequest $request, RangeStudy $rangeStudy): RedirectResponse
    {
        $scenario = $rangeStudy->scenarios()->create($request->scenarioData());

        return redirect()->route('admin.range-studies.edit', $rangeStudy)
            ->with('status', "Scenario \"{$scenario->button_label}\" added.");
    }

    public function edit(RangeScenario $rangeScenario): View
    {
        return view('admin.range-scenarios.edit', [
            'study' => $rangeScenario->study,
            'scenario' => $rangeScenario,
        ]);
    }

    public function update(RangeScenarioRequest $request, RangeScenario $rangeScenario): RedirectResponse
    {
        $rangeScenario->update($request->scenarioData());

        return redirect()->route('admin.range-studies.edit', $rangeScenario->study)
            ->with('status', "Scenario \"{$rangeScenario->button_label}\" updated.");
    }

    public function destroy(RangeScenario $rangeScenario): RedirectResponse
    {
        $study = $rangeScenario->study;
        $label = $rangeScenario->button_label;
        $rangeScenario->delete();

        return redirect()->route('admin.range-studies.edit', $study)
            ->with('status', "Scenario \"{$label}\" deleted.");
    }
}
