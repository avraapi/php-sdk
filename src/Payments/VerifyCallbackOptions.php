<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

final readonly class VerifyCallbackOptions
{
    /** @param array<string, scalar|null> $payload */
    public function __construct(public GatewayCode $gateway, public array $payload, public string $orderId, public string $amount, public string $currency, public string $flow = 'checkout', public ?string $startupFee = null)
    {
        if ($orderId === '' || $amount === '' || $currency === '') {
            throw new InvalidArgumentException('Expected order ID, amount, and currency are required.');
        }
        if (! in_array($flow, ['checkout', 'recurring'], true)) {
            throw new InvalidArgumentException('Payment callback flow must be checkout or recurring.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['gateway' => $this->gateway->value, 'flow' => $this->flow, 'payload' => $this->payload, 'expected' => array_filter(['order_id' => $this->orderId, 'amount' => $this->amount, 'currency' => strtoupper($this->currency), 'startup_fee' => $this->startupFee], static fn (mixed $value): bool => $value !== null)];
    }
}
