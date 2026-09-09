<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function pricing(Request $request): View
    {
        $user = $request->user();
        $name = config('pokercoinverter.subscription_name', 'default');

        return view('subscription.pricing', [
            'plans' => config('pokercoinverter.plans'),
            'trialDays' => (int) config('pokercoinverter.trial_days'),
            'currentPlan' => $user?->subscription($name)?->stripe_price,
            'subscribed' => (bool) $user?->subscribed($name),
        ]);
    }

    /**
     * Send the user to Stripe Checkout for the chosen plan.
     */
    public function checkout(Request $request, string $plan): RedirectResponse|Redirector
    {
        $plans = config('pokercoinverter.plans');
        abort_unless(isset($plans[$plan]), 404);

        $priceId = $plans[$plan]['price_id'];

        if (blank($priceId)) {
            return back()->withErrors([
                'plan' => "The \"{$plan}\" plan has no Stripe price configured. Set STRIPE_PRICE_".strtoupper($plan).' in your .env.',
            ]);
        }

        $user = $request->user();
        $name = config('pokercoinverter.subscription_name', 'default');

        if ($user->subscribed($name)) {
            return redirect()->route('billing');
        }

        $trialDays = (int) config('pokercoinverter.trial_days');

        $builder = $user->newSubscription($name, $priceId);
        if ($trialDays > 0) {
            $builder->trialDays($trialDays);
        }

        return $builder->checkout([
            'success_url' => route('subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('pricing'),
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        return redirect()
            ->route('dashboard')
            ->with('status', 'Subscription active — you can start converting hand histories.');
    }

    /**
     * Redirect to the Stripe-hosted billing portal (update card, invoices, cancel).
     */
    public function billingPortal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('dashboard'));
    }

    /**
     * Resume a subscription that is in its grace period after cancellation.
     */
    public function resume(Request $request): RedirectResponse
    {
        $name = config('pokercoinverter.subscription_name', 'default');
        $subscription = $request->user()->subscription($name);

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            return back()->with('status', 'Subscription resumed.');
        }

        return back();
    }

    /**
     * Cancel at period end (keeps access until the grace period runs out).
     */
    public function cancel(Request $request): RedirectResponse
    {
        $name = config('pokercoinverter.subscription_name', 'default');
        $subscription = $request->user()->subscription($name);

        try {
            $subscription?->cancel();
        } catch (IncompletePayment) {
            // Nothing to do — an incomplete subscription is effectively inactive.
        }

        return back()->with('status', 'Subscription will end at the close of the current billing period.');
    }
}
