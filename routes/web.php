<?php

use App\Http\Controllers\ConverterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
| Public marketing site
*/
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::view('/terms', 'marketing.terms')->name('terms');
Route::view('/privacy', 'marketing.privacy')->name('privacy');

/*
| Authenticated app ("the back")
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Billing / subscription
    Route::get('/account/plans', [SubscriptionController::class, 'pricing'])->name('subscription.plans');
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/billing', [SubscriptionController::class, 'billingPortal'])->name('billing');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Converter (requires an active subscription)
    Route::middleware('subscribed')->group(function () {
        Route::get('/convert', [ConverterController::class, 'create'])->name('convert.create');
        Route::post('/convert', [ConverterController::class, 'store'])->name('convert.store');
        Route::get('/conversions/{conversion}', [ConverterController::class, 'show'])->name('conversions.show');
        Route::get('/conversions/{conversion}/download', [ConverterController::class, 'download'])->name('conversions.download');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
