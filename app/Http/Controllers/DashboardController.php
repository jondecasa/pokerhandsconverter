<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'recent' => $user->conversions()->latest()->take(10)->get(),
            'stats' => [
                'files' => $user->conversions()->count(),
                'hands' => (int) $user->conversions()->sum('hand_count'),
            ],
        ]);
    }
}
