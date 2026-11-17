<?php

namespace App\Support;

use App\Models\Plan;
use Laravel\Cashier\Subscription;

/**
 * Tells the owner on Telegram when somebody gets a package: a free one granted on the site, or a paid one that
 * Stripe has just made active or started a trial for (Cashier creates the row from Stripe's webhook).
 */
class SubscriptionAlert
{
    private const LIVE = ['active', 'trialing'];

    public function __construct(private readonly Telegram $telegram) {}

    public function created(Subscription $subscription): void
    {
        if ($this->isLive((string) $subscription->stripe_status)) {
            $this->notify($subscription);
        }
    }

    /** A paid package can start as "incomplete" and become active once the first payment clears. */
    public function updated(Subscription $subscription): void
    {
        if ($subscription->wasChanged('stripe_status')
            && $this->isLive((string) $subscription->stripe_status)
            && $subscription->getOriginal('stripe_status') === 'incomplete') {
            $this->notify($subscription);
        }
    }

    public function message(Subscription $subscription): string
    {
        $user = $subscription->user;
        $plan = Plan::all()->first(fn (Plan $plan) => $plan->priceKey() === $subscription->stripe_price);

        $lines = ['<b>Nueva suscripción</b>'];
        $lines[] = '👤 '.e($user?->name ?? 'Usuario desconocido').($user ? ' ('.e($user->email).')' : '');

        if ($plan) {
            $price = $plan->isFree()
                ? 'gratis'
                : number_format((float) $plan->price, 2).' '.e($plan->currency).' / '.($plan->interval === 'year' ? 'año' : 'mes');
            $lines[] = '📦 '.e($plan->name).' — '.$price;
        } else {
            $lines[] = '📦 Paquete: '.e((string) $subscription->stripe_price);
        }

        if ($subscription->onTrial()) {
            $lines[] = '🧪 En prueba gratuita hasta el '.$subscription->trial_ends_at->format('d/m/Y');
        }

        return implode("\n", $lines);
    }

    private function notify(Subscription $subscription): void
    {
        if (! $this->telegram->enabled()) {
            return;
        }

        $message = $this->message($subscription);

        // after the response, so a slow Telegram never delays the visitor or the Stripe webhook
        defer(fn () => $this->telegram->send($message));
    }

    private function isLive(string $status): bool
    {
        return in_array($status, self::LIVE, true);
    }
}
