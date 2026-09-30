<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

/** Server-only gateway callback/return completion request. */
final readonly class PaymentCompletionOptions
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public GatewayCode $gateway,
        public string $completionContext,
        public array $payload,
        public PaymentResponseOptions $response = new PaymentResponseOptions,
        public bool $reconcileProvider = true,
    ) {
        if (trim($this->completionContext) === '') {
            throw new InvalidArgumentException('A signed payment completion context is required.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'gateway' => $this->gateway->value,
            'completion_context' => $this->completionContext,
            'payload' => $this->payload,
            'response' => $this->response->toArray(),
            'reconcile_provider' => $this->reconcileProvider,
        ];
    }
}
