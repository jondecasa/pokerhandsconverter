<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', [
            'plan' => new Plan([
                'currency' => 'USD',
                'interval' => 'month',
                'is_visible' => true,
                'is_active' => true,
                'features' => [],
            ]),
            'stakes' => config('pokerhandsconverter.stakes'),
        ]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = Plan::create($request->planData());

        return redirect()->route('admin.plans.index')
            ->with('status', "Package \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', [
            'plan' => $plan,
            'stakes' => config('pokerhandsconverter.stakes'),
        ]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planData());

        return redirect()->route('admin.plans.index')
            ->with('status', "Package \"{$plan->name}\" updated.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('status', "Package \"{$name}\" deleted.");
    }
}
