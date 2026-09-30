<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\OnePay;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

/** Server-side OnePay transaction status lookup. */
final class OnePayService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return array<string, mixed> */
    public function status(string $onePayTransactionId, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        $environment = GatewayEnvironment::configuredFor(GatewayCode::OnePay, $gatewayEnvironment);
        $body = ['onepay_transaction_id' => $onePayTransactionId];
        if ($environment !== null) {
            $body['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/onepay/status', $body, ['X-AvraAPI-Completion-Delivery' => 'merchant-server']);

        return is_array($response->data) ? $response->data : [];
    }
}
