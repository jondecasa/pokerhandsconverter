<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    public function pricing(Request $request): View
    {
        $user = $request->user();
        $name = config('pokerhandsconverter.subscription_name', 'default');

        return view('subscription.pricing', [
            'plans' => Plan::visible()->ordered()->get(),
            'currentPrice' => $user?->subscription($name)?->stripe_price,
            'subscribed' => (bool) $user?->subscribed($name),
        ]);
    }

    /**
     * Send the user to Stripe Checkout for the chosen package. Works for any
     * active package (a hidden one can still be reached by its direct link).
     */
    public function checkout(Request $request, Plan $plan): RedirectResponse|Redirector|Checkout
    {
        abort_unless($plan->is_active, 404);

        $user = $request->user();
        $name = config('pokerhandsconverter.subscription_name', 'default');

        if ($user->subscribed($name)) {
            return $user->hasStripeId()
                ? redirect()->route('billing')
                : redirect()->route('dashboard');
        }

        // Free ($0, no Stripe price) package — grant access locally, no checkout.
        if ($plan->isFree()) {
            $user->subscriptions()->create([
                'type' => $name,
                'stripe_id' => 'free_'.Str::uuid()->toString(),
                'stripe_status' => 'active',
                'stripe_price' => $plan->priceKey(),
                'quantity' => 1,
            ]);

            return redirect()->route('subscription.success');
        }

        if (blank($plan->stripe_price_id)) {
            return back()->withErrors([
                'plan' => "The \"{$plan->name}\" package costs money but has no Stripe price set. Add one in Admin → Packages.",
            ]);
        }

        $builder = $user->newSubscription($name, $plan->stripe_price_id);
        if ($plan->effectiveTrialDays() > 0) {
            // trialDays() would end the trial at "now + N days", the same
            // time-of-day it was started. Stripe Checkout works out the trial
            // length it displays from that timestamp at page-load time, a few
            // seconds after we compute it here — just enough drift to round
            // the shown count down to N-1 for the whole first day. Rounding
            // the end up to midnight keeps the full N days (plus a few spare
            // hours) so the displayed count matches the configured one.
            $builder->trialUntil(now()->addDays($plan->effectiveTrialDays())->endOfDay());
        }

        return $builder->checkout([
            'success_url' => route('subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscription.plans'),
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
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return redirect()->route('dashboard')
                ->with('status', 'There is no billing to manage on a free package.');
        }

        return $user->redirectToBillingPortal(route('dashboard'));
    }

    /**
     * Resume a subscription that is in its grace period after cancellation.
     */
    public function resume(Request $request): RedirectResponse
    {
        $name = config('pokerhandsconverter.subscription_name', 'default');
        $subscription = $request->user()->subscription($name);

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            return back()->with('status', 'Subscription resumed.');
        }

        return back();
    }

    /**
     * Cancel at period end (keeps access until the grace period runs out).
     * A free (local) package is simply removed.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $name = config('pokerhandsconverter.subscription_name', 'default');
        $subscription = $request->user()->subscription($name);

        if (! $subscription) {
            return back();
        }

        if (str_starts_with((string) $subscription->stripe_id, 'free_')) {
            $subscription->delete();

            return back()->with('status', 'Free package removed.');
        }

        try {
            $subscription->cancel();
        } catch (IncompletePayment) {
            // Nothing to do — an incomplete subscription is effectively inactive.
        }

        return back()->with('status', 'Subscription will end at the close of the current billing period.');
    }
}
