<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

final class RetrievalService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return list<array<string, mixed>> Privacy-safe payment projection only. */
    public function findByOrderId(string $orderId, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        /** @var ApiResponse $response */
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        $response = $this->http->get('/payments/payhere/retrieval', array_filter(['order_id' => $orderId, 'gateway_environment' => $environment?->value], static fn (?string $value): bool => $value !== null));

        return is_array($response->data['payments'] ?? null) ? $response->data['payments'] : [];
    }
}
