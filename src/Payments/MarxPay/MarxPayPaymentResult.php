<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\MarxPay;

use RuntimeException;

/** Safe projection only: raw MarxPay gateway/card response data is never returned. */
final readonly class MarxPayPaymentResult
{
    public function __construct(
        public string $merchantRid,
        public string $trId,
        public string $paymentStatus,
        public string $providerStatus,
        public ?string $amount,
        public ?string $currency,
        public ?string $paymentMethod,
        public ?string $expiresAt,
        public string $requestId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        if (! is_scalar($data['merchant_rid'] ?? null) || ! is_scalar($data['tr_id'] ?? null) || ! is_scalar($data['payment_status'] ?? null) || ! is_scalar($data['provider_status'] ?? null)) {
            throw new RuntimeException('APIX returned an invalid MarxPay payment result.');
        }

        return new self(
            (string) $data['merchant_rid'], (string) $data['tr_id'], (string) $data['payment_status'], (string) $data['provider_status'],
            isset($data['amount']) && is_scalar($data['amount']) ? (string) $data['amount'] : null,
            isset($data['currency']) && is_scalar($data['currency']) ? (string) $data['currency'] : null,
            isset($data['payment_method']) && is_scalar($data['payment_method']) ? (string) $data['payment_method'] : null,
            isset($data['expires_at']) && is_scalar($data['expires_at']) ? (string) $data['expires_at'] : null,
            $requestId,
        );
    }
}
