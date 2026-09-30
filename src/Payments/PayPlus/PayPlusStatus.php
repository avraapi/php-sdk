<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayPlus;

use RuntimeException;

/** PayPlus status reconciliation result. This is not payment fulfilment proof; use completePayment() for the signed callback. */
final readonly class PayPlusStatus
{
    public function __construct(
        public string $orderId,
        public string $providerStatus,
        public ?string $timestamp,
        public string $requestId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        if (! is_scalar($data['order_id'] ?? null) || ! is_scalar($data['provider_status'] ?? null)) {
            throw new RuntimeException('APIX returned an invalid PayPlus payment status.');
        }

        return new self(
            (string) $data['order_id'],
            (string) $data['provider_status'],
            isset($data['timestamp']) && is_scalar($data['timestamp']) ? (string) $data['timestamp'] : null,
            $requestId,
        );
    }
}
