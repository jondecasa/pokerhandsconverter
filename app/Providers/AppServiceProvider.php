<?php

namespace App\Providers;

use App\Support\SubscriptionAlert;
use Illuminate\Support\ServiceProvider;
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
    }
}
