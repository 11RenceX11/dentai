<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around PayMongo Hosted Checkout (v2) — create a checkout page for a bill,
 * then mark the bill paid when PayMongo's signed webhook says so.
 */
class PaymongoService
{
    public function enabled(): bool
    {
        return filled(config('paymongo.secret_key'));
    }

    /** @return array{id:string,url:string} */
    public function createCheckout(Billing $billing, string $successUrl, string $cancelUrl): array
    {
        $centavos = (int) round($billing->balance() * 100);

        $response = Http::withBasicAuth((string) config('paymongo.secret_key'), '')
            ->acceptJson()->asJson()->timeout(20)
            ->post(rtrim(config('paymongo.base_url'), '/') . '/v2/checkout_sessions', [
                'data' => ['attributes' => [
                    'line_items' => [[
                        'name' => "Dental bill #{$billing->id}",
                        'amount' => $centavos,          // PayMongo amounts are in centavos
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => config('paymongo.methods'),
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'reference_number' => "BILL-{$billing->id}",
                    'send_email_receipt' => false,
                    'metadata' => ['billing_id' => (string) $billing->id],
                ]],
            ]);

        if ($response->failed() || ! $response->json('data.attributes.checkout_url')) {
            $detail = $response->json('errors.0.detail') ?? 'PayMongo did not return a checkout page.';
            throw new RuntimeException($detail);
        }

        return ['id' => $response->json('data.id'), 'url' => $response->json('data.attributes.checkout_url')];
    }

    /**
     * Check the Paymongo-Signature header: HMAC-SHA256 of "{timestamp}.{raw body}" with the webhook secret,
     * compared with `te` (test mode) or `li` (live mode).
     */
    public function verifySignature(?string $header, string $rawBody): bool
    {
        $secret = (string) config('paymongo.webhook_secret');
        if (! $header || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$key, $value] = array_pad(explode('=', $piece, 2), 2, '');
            $parts[trim($key)] = trim($value);
        }

        $expected = hash_hmac('sha256', ($parts['t'] ?? '') . '.' . $rawBody, $secret);

        foreach (['te', 'li'] as $key) {
            if (($parts[$key] ?? '') !== '' && hash_equals($expected, $parts[$key])) {
                return true;
            }
        }

        return false;
    }

    /** Record a paid checkout session against its bill. Safe to call twice for the same payment. */
    public function recordPaidSession(array $session): void
    {
        $billingId = data_get($session, 'attributes.metadata.billing_id')
            ?? (int) str_replace('BILL-', '', (string) data_get($session, 'attributes.reference_number'));

        $paid = collect(data_get($session, 'attributes.payments', []))
            ->first(fn ($p) => data_get($p, 'attributes.status') === 'paid');

        if (! $billingId || ! $paid) {
            return;
        }

        DB::transaction(function () use ($billingId, $paid) {
            $billing = Billing::lockForUpdate()->find($billingId);
            if (! $billing || Payment::where('provider_reference', $paid['id'])->exists()) {
                return;
            }

            $amount = min(((float) data_get($paid, 'attributes.amount', 0)) / 100, $billing->balance());
            if ($amount <= 0) {
                return;
            }

            $billing->payments()->create([
                'amount_paid' => $amount,
                'payment_date' => today(),
                'payment_method' => 'paymongo',
                'provider_reference' => $paid['id'],
            ]);
            $billing->refreshStatus();
        });
    }
}
