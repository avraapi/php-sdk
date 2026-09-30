<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;
use InvalidArgumentException;

final class CapturesService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return array<string, mixed> */
    public function create(string $idempotencyKey, string $authorizationToken, string $amount, string $expectedAuthorizedAmount, string $expectedOrderId, string $currency, string $deductionDetails, bool $confirmCapture, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        if (! $confirmCapture) {
            throw new InvalidArgumentException('confirmCapture must be true to capture a PayHere authorization.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        /** @var ApiResponse $response */ $response = $this->http->post('/payments/payhere/captures', array_filter(['authorization_token' => $authorizationToken, 'amount' => $amount, 'expected_authorized_amount' => $expectedAuthorizedAmount, 'expected_order_id' => $expectedOrderId, 'currency' => $currency, 'deduction_details' => $deductionDetails, 'confirm_capture' => true, 'gateway_environment' => $environment?->value], static fn (mixed $value): bool => $value !== null), ['Idempotency-Key' => $idempotencyKey]);

        return $response->data;
    }
}
