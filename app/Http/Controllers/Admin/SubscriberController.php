<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\View\View;
use Laravel\Cashier\Subscription;

class SubscriberController extends Controller
{
    public function index(): View
    {
        $plans = Plan::ordered()->get();
        $plansByPriceKey = $plans->keyBy(fn (Plan $plan) => $plan->priceKey());

        // toBase(): groupBy() on an Eloquent Collection keeps its class, and
        // except() on the grouped result then assumes each group is a single
        // model (calls getKey() on it) instead of a group of models.
        $subscriptions = Subscription::query()
            ->where('type', config('pokerhandsconverter.subscription_name', 'default'))
            ->with('user')
            ->latest()
            ->get()
            ->each(fn (Subscription $subscription) => $subscription->package = $plansByPriceKey->get($subscription->stripe_price))
            ->toBase()
            ->groupBy('stripe_price');

        $tabs = $plans->map(fn (Plan $plan) => [
            'plan' => $plan,
            'subscriptions' => $subscriptions->get($plan->priceKey(), collect()),
        ]);

        // Rows whose stripe_price doesn't match any current package (a
        // renamed/deleted package, or a Stripe price no longer configured).
        $knownKeys = $plans->map(fn (Plan $plan) => $plan->priceKey())->all();
        $other = $subscriptions->except($knownKeys)->flatten(1)->sortByDesc('created_at')->values();

        $defaultTab = optional($tabs->sortByDesc(fn ($tab) => $tab['subscriptions']->count())->first())['plan'];

        return view('admin.subscribers.index', [
            'tabs' => $tabs,
            'other' => $other,
            'defaultTab' => $defaultTab?->slug ?? 'other',
        ]);
    }
}
