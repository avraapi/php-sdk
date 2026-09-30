<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;
use InvalidArgumentException;

final class RefundsService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return array<string, mixed> */
    public function create(string $idempotencyKey, ?string $paymentId, ?string $authorizationToken, string $description, bool $confirmRefund, ?string $amount = null, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        if ((trim((string) $paymentId) === '') === (trim((string) $authorizationToken) === '')) {
            throw new InvalidArgumentException('Provide exactly one PayHere payment ID or authorization token.');
        }
        if (! $confirmRefund) {
            throw new InvalidArgumentException('confirmRefund must be true to issue a refund.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/payhere/refunds', array_filter([
            'payment_id' => $paymentId, 'authorization_token' => $authorizationToken,
            'description' => $description, 'confirm_refund' => true, 'amount' => $amount, 'gateway_environment' => $environment?->value,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''), ['Idempotency-Key' => $idempotencyKey]);

        return $response->data;
    }
}
