<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\WebXPay;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

/** Server-side WebXPay Merchant API lookup; browser returns are never proof. */
final class WebXPayService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return array<string, mixed> */
    public function status(string $orderId, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        $environment = GatewayEnvironment::configuredFor(GatewayCode::WebXPay, $gatewayEnvironment);
        $body = ['order_id' => $orderId];
        if ($environment !== null) {
            $body['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/webxpay/status', $body, ['X-AvraAPI-Completion-Delivery' => 'merchant-server']);

        return is_array($response->data) ? $response->data : [];
    }
}
