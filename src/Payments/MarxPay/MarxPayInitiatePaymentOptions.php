<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\MarxPay;

use Avraapi\Apix\Payments\GatewayEnvironment;
use InvalidArgumentException;

final readonly class MarxPayInitiatePaymentOptions
{
    public function __construct(public string $trId, public string $merchantRid, public ?GatewayEnvironment $gatewayEnvironment = null)
    {
        if (trim($trId) === '' || trim($merchantRid) === '') {
            throw new InvalidArgumentException('A MarxPay trId and merchantRID are required.');
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return array_filter(['tr_id' => $this->trId, 'merchant_rid' => $this->merchantRid, 'gateway_environment' => $this->gatewayEnvironment?->value], static fn (?string $value): bool => $value !== null);
    }
}
