<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Koko;

use RuntimeException;

/** Signed KOKO order-view result. Use completePayment() for fulfilment. */
final readonly class KokoOrderView
{
    /** @param array<string, mixed> $native */
    public function __construct(
        public string $orderId,
        public string $gatewayReference,
        public string $providerStatus,
        public array $native,
        public string $requestId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        foreach (['order_id', 'gateway_reference', 'provider_status'] as $field) {
            if (! is_scalar($data[$field] ?? null) || trim((string) $data[$field]) === '') {
                throw new RuntimeException('APIX returned an invalid KOKO order-view result.');
            }
        }

        return new self(
            (string) $data['order_id'],
            (string) $data['gateway_reference'],
            (string) $data['provider_status'],
            is_array($data['native'] ?? null) ? $data['native'] : [],
            $requestId,
        );
    }
}
