<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;
use InvalidArgumentException;

final class ChargesService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return array<string, mixed> */
    public function create(string $idempotencyKey, string $orderId, string $items, string $currency, string $amount, string $customerToken, bool $confirmCharge, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        if (! $confirmCharge) {
            throw new InvalidArgumentException('confirmCharge must be true to create a PayHere token charge.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        /** @var ApiResponse $response */ $response = $this->http->post('/payments/payhere/charges', array_filter(['order_id' => $orderId, 'items' => $items, 'currency' => $currency, 'amount' => $amount, 'customer_token' => $customerToken, 'confirm_charge' => true, 'gateway_environment' => $environment?->value], static fn (mixed $value): bool => $value !== null), ['Idempotency-Key' => $idempotencyKey]);

        return $response->data;
    }
}
