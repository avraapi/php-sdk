<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use RuntimeException;

final readonly class VerificationResult
{
    public function __construct(
        public bool $verified,
        public PaymentStatus $paymentStatus,
        public string $providerStatus,
        public string $orderId,
        public ?string $gatewayReference,
        public string $amount,
        public string $currency,
        public string $requestId,
        public string $flow = 'checkout',
        /** @var array<string, string|null>|null */
        public ?array $subscription = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        $status = PaymentStatus::tryFrom((string) ($data['payment_status'] ?? ''));
        if ($status === null || ! isset($data['order_id'], $data['amount'], $data['currency'])) {
            throw new RuntimeException('APIX returned an invalid payment verification result.');
        }

        return new self((bool) ($data['verified'] ?? false), $status, (string) ($data['provider_status'] ?? ''), (string) $data['order_id'], isset($data['gateway_reference']) ? (string) $data['gateway_reference'] : null, (string) $data['amount'], (string) $data['currency'], $requestId, (string) ($data['flow'] ?? 'checkout'), is_array($data['subscription'] ?? null) ? $data['subscription'] : null);
    }
}
