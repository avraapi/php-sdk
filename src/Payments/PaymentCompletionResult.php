<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use RuntimeException;

/** One normalized result regardless of the gateway's native completion flow. */
final readonly class PaymentCompletionResult
{
    /** @param array<string, mixed> $nativeOperations @param array<string, mixed> $include @param array<string, mixed> $reconciliation */
    public function __construct(
        public GatewayCode $gateway,
        public bool $verified,
        public PaymentStatus $paymentStatus,
        public string $providerStatus,
        public ?string $orderId,
        public ?string $gatewayReference,
        public ?string $amount,
        public ?string $currency,
        public string $requestId,
        public array $nativeOperations = [],
        public array $include = [],
        /** Safe metadata describing whether a secondary provider observation agreed with the authenticated callback. */
        public array $reconciliation = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        $gateway = GatewayCode::tryFrom((string) ($data['gateway'] ?? ''));
        $status = PaymentStatus::tryFrom((string) ($data['payment_status'] ?? ''));
        if ($gateway === null || $status === null || ! isset($data['provider_status'])) {
            throw new RuntimeException('APIX returned an invalid payment completion result.');
        }

        return new self(
            $gateway,
            (bool) ($data['verified'] ?? false),
            $status,
            (string) $data['provider_status'],
            self::nullableString($data['order_id'] ?? null),
            self::nullableString($data['gateway_reference'] ?? null),
            self::nullableString($data['amount'] ?? null),
            self::nullableString($data['currency'] ?? null),
            $requestId,
            is_array($data['native_operations'] ?? null) ? $data['native_operations'] : [],
            is_array($data['include'] ?? null) ? $data['include'] : [],
            is_array($data['reconciliation'] ?? null) ? $data['reconciliation'] : [],
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
