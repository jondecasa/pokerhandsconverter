<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

/**
 * Public, unauthenticated marketing pages. The actual converter lives behind
 * auth + the "subscribed" middleware.
 */
class MarketingController extends Controller
{
    public function home(): View
    {
        return view('welcome', ['plans' => $this->visiblePlans()]);
    }

    public function pricing(): View
    {
        return view('marketing.pricing', ['plans' => $this->visiblePlans()]);
    }

    private function visiblePlans()
    {
        return Plan::visible()->ordered()->get();
    }
}
