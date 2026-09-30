<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use RuntimeException;

final readonly class PaymentSession
{
    /** @param array<string, mixed> $checkout @param array<string, mixed> $binding @param array<string, mixed> $verification */
    public function __construct(
        public GatewayCode $gateway,
        public CheckoutMode $mode,
        public string $status,
        public ?string $expiresAt,
        public array $checkout,
        public string $requestId,
        public string $flow = 'checkout',
        public array $binding = [],
        public array $verification = [],
        /** Never send this signed value to a browser; retain it with the merchant's pending order. */
        public ?string $completionContext = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        $gateway = GatewayCode::tryFrom((string) ($data['gateway'] ?? ''));
        $mode = CheckoutMode::tryFrom((string) ($data['mode'] ?? ''));
        if ($gateway === null || $mode === null || ! is_array($data['checkout'] ?? null)) {
            throw new RuntimeException('APIX returned an invalid payment session.');
        }

        return new self(
            $gateway,
            $mode,
            (string) ($data['status'] ?? 'prepared'),
            isset($data['expires_at']) ? (string) $data['expires_at'] : null,
            $data['checkout'],
            $requestId,
            (string) ($data['flow'] ?? 'checkout'),
            is_array($data['binding'] ?? null) ? $data['binding'] : [],
            is_array($data['verification'] ?? null) ? $data['verification'] : [],
            isset($data['completion_context']) && is_scalar($data['completion_context']) ? (string) $data['completion_context'] : null,
        );
    }
}
