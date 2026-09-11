<?php

namespace Tests\Feature;

use App\Http\Controllers\SubscriptionController;
use Laravel\Cashier\Checkout;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    /**
     * Regression guard: a paid-plan checkout hands back a Laravel\Cashier\Checkout
     * (Cashier's SubscriptionBuilder::checkout() return type), not a
     * RedirectResponse/Redirector. A return type that doesn't allow it makes PHP
     * throw a TypeError on every paid-plan subscribe — see the 500 this once was.
     */
    #[Test]
    public function checkout_return_type_accepts_a_cashier_checkout_response(): void
    {
        $type = (new ReflectionMethod(SubscriptionController::class, 'checkout'))->getReturnType();

        $this->assertNotNull($type, 'checkout() should declare a return type.');
        $this->assertStringContainsString(
            Checkout::class,
            (string) $type,
            'checkout() must allow returning a Laravel\Cashier\Checkout, or a real paid-plan subscribe throws a TypeError.'
        );
    }
}
