<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

/** Server-only PayHere authorization hold request. */
final readonly class AuthorizationOptions
{
    public function __construct(public CreateOrderOptions $order)
    {
        if ($order->gateway !== GatewayCode::PayHere || $order->mode !== CheckoutMode::Redirect) {
            throw new \InvalidArgumentException('PayHere authorization requires a PayHere redirect order.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->order->toArray();
    }
}
