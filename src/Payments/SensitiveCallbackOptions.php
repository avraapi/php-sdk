<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

/** Callback verification whose artifact must remain on the merchant server. */
final readonly class SensitiveCallbackOptions
{
    /** @param array<string, scalar|null> $payload */
    public function __construct(public string $flow, public array $payload, public string $orderId, public string $amount, public string $currency)
    {
        if (! in_array($flow, ['preapproval', 'authorization'], true)) {
            throw new InvalidArgumentException('Sensitive callback flow must be preapproval or authorization.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['gateway' => GatewayCode::PayHere->value, 'flow' => $this->flow, 'payload' => $this->payload, 'expected' => ['order_id' => $this->orderId, 'amount' => $this->amount, 'currency' => strtoupper($this->currency)]];
    }
}
