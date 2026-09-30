<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Koko;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

/** Server-side KOKO reconciliation. Checkout is created with createOrder(). */
final class KokoService
{
    public function __construct(private readonly HttpClient $http) {}

    public function orderView(string $orderId, ?GatewayEnvironment $gatewayEnvironment = null): KokoOrderView
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            throw new \InvalidArgumentException('A KOKO order ID is required.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::Koko, $gatewayEnvironment);
        $payload = ['order_id' => $orderId];
        if ($environment !== null) {
            $payload['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/koko/orders/view', $payload);

        return KokoOrderView::fromArray($response->data, $response->requestId);
    }
}
