<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper over Xendit's Invoice API. We only need to create an invoice and
 * hand back its hosted checkout URL; payment confirmation arrives later on the
 * webhook. Uses the REST API directly through Laravel's HTTP client, so there
 * is no SDK to pin or keep in sync.
 */
class XenditClient
{
    public function isConfigured(): bool
    {
        return filled(config('emailcv.xendit.secret_key'));
    }

    /**
     * Create a hosted invoice and return its id and checkout URL.
     *
     * @return array{id: string, invoice_url: string}
     *
     * @throws PaymentException
     */
    public function createInvoice(
        string $externalId,
        int $amount,
        string $description,
        string $payerEmail,
        string $successUrl,
        string $failureUrl,
    ): array {
        if (! $this->isConfigured()) {
            throw new PaymentException('Payments are not configured yet. Please try again later.');
        }

        try {
            $response = Http::withBasicAuth((string) config('emailcv.xendit.secret_key'), '')
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post(rtrim((string) config('emailcv.xendit.base_url'), '/').'/v2/invoices', [
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'currency' => (string) config('emailcv.xendit.currency', 'IDR'),
                    'description' => $description,
                    'payer_email' => $payerEmail,
                    'success_redirect_url' => $successUrl,
                    'failure_redirect_url' => $failureUrl,
                    // Give the buyer a full day to complete payment.
                    'invoice_duration' => 86400,
                ]);
        } catch (Throwable $e) {
            Log::warning('Xendit invoice request failed to send.', ['error' => $e->getMessage()]);

            throw new PaymentException('We could not reach the payment service. Please try again.');
        }

        if (! $response->successful()) {
            Log::warning('Xendit rejected an invoice request.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new PaymentException('The payment service rejected the request. Please try again.');
        }

        $data = $response->json();

        if (! isset($data['id'], $data['invoice_url'])) {
            throw new PaymentException('The payment service returned an unexpected response.');
        }

        return [
            'id' => (string) $data['id'],
            'invoice_url' => (string) $data['invoice_url'],
        ];
    }
}
