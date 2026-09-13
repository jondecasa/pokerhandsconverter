<?php

use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\ContactController;
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
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->middleware('throttle:5,1')->name('contact.submit');

/*
| Authenticated app ("the back")
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Billing / subscription
    Route::get('/account/plans', [SubscriptionController::class, 'pricing'])->name('subscription.plans');
    // GET too: hidden packages are shared as a bare "/subscribe/<slug>" link the
    // customer just clicks (see the admin packages help text) — a POST-only
    // route can't be opened that way, and it also breaks the post-login
    // redirect-back for a logged-out visitor.
    Route::match(['get', 'post'], '/subscribe/{plan}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
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
        Route::delete('/conversions/{conversion}', [ConverterController::class, 'destroy'])->name('conversions.destroy');
        Route::post('/conversions/{conversion}/feedback', [ConverterController::class, 'feedback'])
            ->middleware('throttle:5,1')
            ->name('conversions.feedback');
    });

    // Admin — package / pricing maintenance
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::redirect('/', '/admin/plans');
        Route::resource('plans', AdminPlanController::class)->except('show');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
