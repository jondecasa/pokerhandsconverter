<?php

namespace App\Providers;

use App\Support\PaymentAlert;
use App\Support\SubscriptionAlert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Subscription::created(fn (Subscription $subscription) => app(SubscriptionAlert::class)->created($subscription));
        Subscription::updated(fn (Subscription $subscription) => app(SubscriptionAlert::class)->updated($subscription));

        Event::listen(WebhookReceived::class, fn (WebhookReceived $event) => app(PaymentAlert::class)->handle($event->payload));
    }
}
