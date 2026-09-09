<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $name = config('pokercoinverter.subscription_name', 'default');
        $subscription = $user->subscription($name);

        return view('dashboard', [
            'subscribed' => (bool) $user->subscribed($name),
            'onTrial' => (bool) $user->onTrial($name),
            'onGracePeriod' => (bool) $subscription?->onGracePeriod(),
            'onFreePlan' => str_starts_with((string) $subscription?->stripe_id, 'free_'),
            'subscription' => $subscription,
            'recent' => $user->conversions()->latest()->take(10)->get(),
            'stats' => [
                'files' => $user->conversions()->count(),
                'hands' => (int) $user->conversions()->sum('hand_count'),
            ],
        ]);
    }
}
