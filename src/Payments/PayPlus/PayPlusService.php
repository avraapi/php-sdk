<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayPlus;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

/** Server-side PayPlus operations. Standard checkout itself uses createOrder(). */
final class PayPlusService
{
    public function __construct(private readonly HttpClient $http) {}

    public function status(string $orderId, ?GatewayEnvironment $gatewayEnvironment = null): PayPlusStatus
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new \InvalidArgumentException('A PayPlus order ID is required.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayPlus, $gatewayEnvironment);
        $payload = ['order_id' => $orderId];
        if ($environment !== null) {
            $payload['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/payplus/status', $payload);

        return PayPlusStatus::fromArray($response->data, $response->requestId);
    }
}
