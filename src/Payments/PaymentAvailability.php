<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

/**
 * Server-side preflight result for safely bootstrapping Payment Elements.
 *
 * It intentionally contains only public checkout metadata and may be embedded
 * into HTML. APIX/gateway credentials and Workspace identifiers are excluded.
 */
final class PaymentAvailability
{
    /** @param list<PaymentMethodAvailability> $methods */
    private function __construct(
        public readonly bool $ready,
        public readonly ?PaymentAvailabilityReason $reason,
        public readonly ?string $message,
        public readonly array $methods,
        public readonly ?string $requestId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, ?string $requestId = null): self
    {
        $rawMethods = is_array($data['methods'] ?? null) ? $data['methods'] : [];
        $methods = [];
        foreach ($rawMethods as $method) {
            if (is_array($method)) {
                $methods[] = PaymentMethodAvailability::fromArray($method);
            }
        }

        return new self(
            ready: ($data['ready'] ?? false) === true,
            reason: PaymentAvailabilityReason::fromApi($data['reason'] ?? null),
            message: isset($data['message']) && is_scalar($data['message']) ? trim((string) $data['message']) ?: null : null,
            methods: $methods,
            requestId: $requestId,
        );
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    /** @return list<array<string, mixed>> */
    public function methods(): array
    {
        return array_map(static fn (PaymentMethodAvailability $method): array => $method->toArray(), $this->methods);
    }

    /** @return array{ready: bool, reason: null|string, message: null|string, methods: list<array<string, mixed>>} */
    public function toElementsPayload(): array
    {
        return [
            'ready' => $this->ready,
            'reason' => $this->reason?->value,
            'message' => $this->message,
            'methods' => $this->methods(),
        ];
    }
}
