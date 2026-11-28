<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Throwable;

/**
 * Looks up why the latest payment of a Stripe invoice failed. Since API 2025-03 an invoice no longer carries its
 * PaymentIntent, so this goes invoice → invoice payments → PaymentIntent (with its payment method and latest charge).
 * Never throws: when Stripe cannot be reached the alert is sent without these details.
 */
class StripePaymentDetails
{
    /**
     * @return array{payment_intent: ?string, code: ?string, decline_code: ?string, message: ?string,
     *     outcome_type: ?string, seller_message: ?string, network_reason: ?string, method: ?string}|null
     */
    public function forInvoice(string $invoiceId): ?array
    {
        if (blank(config('cashier.secret'))) {
            return null;
        }

        try {
            $stripe = Cashier::stripe();
            $payments = $stripe->invoicePayments->all(['invoice' => $invoiceId, 'limit' => 10])->data;
            $intentId = collect($payments)
                ->sortByDesc('created')
                ->map(fn ($payment) => $payment->payment->payment_intent ?? null)
                ->first(fn ($id) => is_string($id) && $id !== '');

            if (! $intentId) {
                return null;
            }

            $intent = $stripe->paymentIntents->retrieve($intentId, ['expand' => ['payment_method', 'latest_charge']]);
        } catch (Throwable $e) {
            Log::warning('Could not fetch the failed payment details from Stripe', ['invoice' => $invoiceId, 'error' => $e->getMessage()]);

            return null;
        }

        $error = $intent->last_payment_error ?? null;
        $charge = is_object($intent->latest_charge ?? null) ? $intent->latest_charge : null;
        $method = is_object($intent->payment_method ?? null) ? $intent->payment_method : ($error->payment_method ?? null);

        return [
            'payment_intent' => $intent->id,
            'code' => $error->code ?? $charge?->failure_code ?? null,
            'decline_code' => $error->decline_code ?? $charge?->outcome?->reason ?? null,
            'message' => $error->message ?? $charge?->failure_message ?? null,
            'outcome_type' => $charge?->outcome?->type ?? null,
            'seller_message' => $charge?->outcome?->seller_message ?? null,
            'network_reason' => $charge?->outcome?->network_decline_code ?? null,
            'method' => $method ? self::describeMethod($method) : null,
        ];
    }

    /** "Visa ····4242 (BR)", "Link", "SEPA Debit"… */
    public static function describeMethod(object $method): string
    {
        $type = (string) ($method->type ?? 'desconocido');

        if ($type === 'card' && isset($method->card)) {
            $card = $method->card;
            $text = ucfirst((string) ($card->brand ?? 'tarjeta')).' ····'.($card->last4 ?? '????');
            if (! empty($card->funding)) {
                $text .= ' '.$card->funding;
            }

            return ! empty($card->country) ? $text.' ('.$card->country.')' : $text;
        }

        return match ($type) {
            'link' => 'Link (monedero de Stripe)',
            'sepa_debit' => 'Adeudo SEPA',
            'paypal' => 'PayPal',
            'amazon_pay' => 'Amazon Pay',
            default => str_replace('_', ' ', $type),
        };
    }
}
