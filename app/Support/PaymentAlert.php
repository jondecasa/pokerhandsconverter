<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;

/**
 * Tells the owner on Telegram about money moving in Stripe: a charge that went through, a payment that failed (with
 * the decline reason, to debug it without opening the dashboard) and one that waits for the customer's 3D Secure.
 * Fed by Cashier's WebhookReceived event, so the Stripe webhook endpoint must send these invoice events.
 */
class PaymentAlert
{
    /** Stripe decline codes in plain Spanish. https://docs.stripe.com/declines/codes */
    private const DECLINES = [
        'insufficient_funds' => 'fondos insuficientes',
        'generic_decline' => 'rechazo genérico del banco (no da motivo)',
        'do_not_honor' => 'el banco lo rechazó sin dar motivo',
        'expired_card' => 'tarjeta caducada',
        'incorrect_cvc' => 'CVC incorrecto',
        'incorrect_number' => 'número de tarjeta incorrecto',
        'card_not_supported' => 'la tarjeta no admite este tipo de compra',
        'currency_not_supported' => 'la tarjeta no admite esta moneda',
        'card_velocity_exceeded' => 'superó el límite de la tarjeta',
        'withdrawal_count_limit_exceeded' => 'superó el límite de operaciones',
        'transaction_not_allowed' => 'el banco no permite esta operación',
        'authentication_required' => 'el banco exige autenticación (3D Secure)',
        'lost_card' => 'tarjeta denunciada como perdida',
        'stolen_card' => 'tarjeta denunciada como robada',
        'pickup_card' => 'el banco pide retener la tarjeta',
        'fraudulent' => 'el banco sospecha fraude',
        'merchant_blacklist' => 'bloqueado por las reglas de Radar',
        'highest_risk_level' => 'Radar lo bloqueó por riesgo alto',
        'processing_error' => 'error de procesamiento, se puede reintentar',
        'try_again_later' => 'el banco pide reintentar más tarde',
        'issuer_not_available' => 'no se pudo contactar con el banco',
        'invalid_account' => 'cuenta o tarjeta no válida',
        'previously_declined_do_not_retry' => 'rechazada antes, el banco pide no reintentar',
        'no_action_taken' => 'el banco no procesó la operación',
        'service_not_allowed' => 'el banco no permite este servicio',
        'stop_payment_order' => 'el cliente ordenó parar los cobros',
        'revocation_of_authorization' => 'el cliente revocó la autorización',
    ];

    public function __construct(
        private readonly Telegram $telegram,
        private readonly StripePaymentDetails $details,
    ) {}

    /** @param  array<string, mixed>  $payload  a Stripe webhook event */
    public function handle(array $payload): void
    {
        $type = $payload['type'] ?? null;
        $invoice = $payload['data']['object'] ?? null;

        if (! is_array($invoice) || ($invoice['object'] ?? null) !== 'invoice') {
            return;
        }

        $livemode = (bool) ($payload['livemode'] ?? $invoice['livemode'] ?? true);

        match ($type) {
            'invoice.payment_succeeded' => $this->paid($invoice, $livemode),
            'invoice.payment_failed' => $this->failed($invoice, $livemode),
            'invoice.payment_action_required' => $this->actionRequired($invoice, $livemode),
            default => null,
        };
    }

    /** @param  array<string, mixed>  $invoice */
    private function paid(array $invoice, bool $livemode): void
    {
        // A trial starts with a 0.00 invoice: that is a new subscription (already announced), not a charge.
        if ((int) ($invoice['amount_paid'] ?? 0) <= 0) {
            return;
        }

        $lines = ['<b>💰 Cobro recibido</b>'];
        array_push($lines, ...$this->summary($invoice, (int) $invoice['amount_paid']));
        $lines[] = '🔗 <a href="'.e($this->dashboardUrl('invoices/'.$invoice['id'], $livemode)).'">Factura en Stripe</a>';

        $this->send(implode("\n", $lines));
    }

    /** @param  array<string, mixed>  $invoice */
    private function failed(array $invoice, bool $livemode): void
    {
        $invoiceId = (string) ($invoice['id'] ?? '');
        $summary = $this->summary($invoice, (int) ($invoice['amount_due'] ?? 0));
        $attempts = (int) ($invoice['attempt_count'] ?? 0);
        $next = $invoice['next_payment_attempt'] ?? null;
        $payLink = $invoice['hosted_invoice_url'] ?? null;

        // The decline reason needs extra Stripe calls: do them after the webhook has been answered. It is logged
        // even without Telegram, so storage/logs/laravel.log always says why a payment failed.
        defer(function () use ($invoiceId, $summary, $attempts, $next, $payLink, $livemode) {
            $details = $invoiceId !== '' ? $this->details->forInvoice($invoiceId) : null;

            Log::warning('Stripe payment failed', ['invoice' => $invoiceId, 'attempt' => $attempts] + ($details ?? []));

            if (! $this->telegram->enabled()) {
                return;
            }

            $lines = ['<b>⚠️ Pago fallido</b>'];
            array_push($lines, ...$summary);

            if ($details) {
                $lines[] = '❌ Motivo: '.e($this->reason($details));
                if ($details['seller_message']) {
                    $lines[] = '🏦 Stripe: '.e($details['seller_message']);
                }
                if ($details['method']) {
                    $lines[] = '💳 Método: '.e($details['method']);
                }
            } else {
                $lines[] = '❌ Motivo: no disponible (mira el pago en Stripe)';
            }

            $lines[] = '🔁 Intento '.max(1, $attempts).' — '.($next
                ? 'Stripe reintentará el '.Carbon::createFromTimestamp((int) $next, 'UTC')->format('d/m/Y H:i').' UTC'
                : 'no hay más reintentos programados');

            $links = ['<a href="'.e($this->dashboardUrl('invoices/'.$invoiceId, $livemode)).'">Factura</a>'];
            if ($details['payment_intent'] ?? null) {
                $links[] = '<a href="'.e($this->dashboardUrl('payments/'.$details['payment_intent'], $livemode)).'">Pago</a>';
            }
            if ($payLink) {
                $links[] = '<a href="'.e($payLink).'">Enlace de pago para el cliente</a>';
            }
            $lines[] = '🔗 '.implode(' · ', $links);

            $this->telegram->send(implode("\n", $lines));
        });
    }

    /** @param  array<string, mixed>  $invoice */
    private function actionRequired(array $invoice, bool $livemode): void
    {
        $lines = ['<b>🔐 Pago pendiente de 3D Secure</b>'];
        array_push($lines, ...$this->summary($invoice, (int) ($invoice['amount_due'] ?? 0)));
        $lines[] = 'El banco pide que el cliente confirme el pago. Stripe le envía un enlace si el email está activado.';
        $lines[] = '🔗 <a href="'.e($this->dashboardUrl('invoices/'.$invoice['id'], $livemode)).'">Factura en Stripe</a>';

        $this->send(implode("\n", $lines));
    }

    /**
     * Who, how much, which package and why Stripe billed.
     *
     * @param  array<string, mixed>  $invoice
     * @return list<string>
     */
    private function summary(array $invoice, int $amount): array
    {
        $user = is_string($invoice['customer'] ?? null) ? Cashier::findBillable($invoice['customer']) : null;
        $user = $user instanceof User ? $user : null;

        $name = $user?->name ?? ($invoice['customer_name'] ?? null) ?? 'Cliente';
        $email = $user?->email ?? ($invoice['customer_email'] ?? null);

        $lines = ['👤 '.e($name).($email ? ' ('.e($email).')' : '')];
        $money = $this->money($amount, (string) ($invoice['currency'] ?? 'usd'));
        $package = $this->package($user, $invoice);
        $lines[] = '💵 '.$money.($package ? ' — '.e($package) : '');
        $lines[] = '🧾 '.$this->billingReason($invoice, $user);

        return $lines;
    }

    /** @param  array<string, mixed>  $invoice */
    private function package(?User $user, array $invoice): ?string
    {
        $subscription = $user?->subscriptions()->latest()->first();

        if ($subscription) {
            $plan = Plan::all()->first(fn (Plan $plan) => $plan->priceKey() === $subscription->stripe_price);
            if ($plan) {
                return $plan->name;
            }
        }

        $description = $invoice['lines']['data'][0]['description'] ?? null;

        return is_string($description) && $description !== '' ? $description : null;
    }

    /** @param  array<string, mixed>  $invoice */
    private function billingReason(array $invoice, ?User $user): string
    {
        $reason = (string) ($invoice['billing_reason'] ?? '');

        if ($reason === 'subscription_cycle') {
            $trialEnd = $user?->subscriptions()->latest()->first()?->trial_ends_at;
            $created = isset($invoice['created']) ? Carbon::createFromTimestamp((int) $invoice['created']) : null;

            return $trialEnd && $created && abs($created->diffInHours($trialEnd)) <= 48
                ? 'Primer cobro tras la prueba gratuita'
                : 'Renovación';
        }

        return match ($reason) {
            'subscription_create' => 'Alta de suscripción',
            'subscription_update' => 'Cambio de suscripción',
            'manual' => 'Factura manual',
            default => $reason !== '' ? str_replace('_', ' ', $reason) : 'Cobro',
        };
    }

    /** @param  array<string, ?string>  $details */
    private function reason(array $details): string
    {
        $code = $details['decline_code'] ?: $details['code'];
        $text = $code ? (self::DECLINES[$code] ?? str_replace('_', ' ', $code)) : ($details['message'] ?? 'desconocido');

        $codes = array_values(array_unique(array_filter([$details['code'], $details['decline_code'], $details['network_reason']])));

        if ($details['outcome_type'] === 'blocked') {
            $text .= ' — bloqueado por Stripe/Radar, no por el banco';
        }

        return $codes ? $text.' ['.implode(' / ', $codes).']' : $text;
    }

    private function money(int $minor, string $currency): string
    {
        return number_format($minor / 100, 2, ',', '.').' '.strtoupper($currency);
    }

    private function dashboardUrl(string $path, bool $livemode): string
    {
        return 'https://dashboard.stripe.com/'.($livemode ? '' : 'test/').$path;
    }

    /** After the response, so a slow Telegram never delays the Stripe webhook. */
    private function send(string $message): void
    {
        if ($this->telegram->enabled()) {
            defer(fn () => $this->telegram->send($message));
        }
    }
}
