<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;
use InvalidArgumentException;

final class SubscriptionManagerService
{
    public function __construct(private readonly HttpClient $http) {}

    /** @return list<SubscriptionSummary> */
    public function all(?GatewayEnvironment $gatewayEnvironment = null): array
    {
        /** @var ApiResponse $response */
        $response = $this->http->get('/payments/payhere/subscriptions', $this->environmentQuery($gatewayEnvironment));

        return array_map(static fn (array $subscription): SubscriptionSummary => SubscriptionSummary::fromArray($subscription), array_filter((array) ($response->data['subscriptions'] ?? []), 'is_array'));
    }

    public function find(string $subscriptionId, ?GatewayEnvironment $gatewayEnvironment = null): SubscriptionSummary
    {
        /** @var ApiResponse $response */
        $response = $this->http->get('/payments/payhere/subscriptions/'.$this->subscriptionId($subscriptionId), $this->environmentQuery($gatewayEnvironment));

        return SubscriptionSummary::fromArray((array) ($response->data['subscription'] ?? []));
    }

    /** @return list<array<string, mixed>> Privacy-safe payment projection only. */
    public function payments(string $subscriptionId, ?GatewayEnvironment $gatewayEnvironment = null): array
    {
        /** @var ApiResponse $response */
        $response = $this->http->get('/payments/payhere/subscriptions/'.$this->subscriptionId($subscriptionId).'/payments', $this->environmentQuery($gatewayEnvironment));

        return is_array($response->data['payments'] ?? null) ? $response->data['payments'] : [];
    }

    public function retry(string $idempotencyKey, string $subscriptionId, bool $confirmRetry, ?GatewayEnvironment $gatewayEnvironment = null): SubscriptionCommandResult
    {
        if (! $confirmRetry) {
            throw new InvalidArgumentException('confirmRetry must be true to retry a subscription.');
        }
        /** @var ApiResponse $response */
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        $response = $this->http->post('/payments/payhere/subscriptions/retry', array_filter(['subscription_id' => $this->subscriptionId($subscriptionId), 'confirm_retry' => true, 'gateway_environment' => $environment?->value], static fn (mixed $value): bool => $value !== null), ['Idempotency-Key' => $idempotencyKey]);

        return SubscriptionCommandResult::fromArray($response->data);
    }

    public function cancel(string $idempotencyKey, string $subscriptionId, bool $confirmCancel, ?GatewayEnvironment $gatewayEnvironment = null): SubscriptionCommandResult
    {
        if (! $confirmCancel) {
            throw new InvalidArgumentException('confirmCancel must be true to cancel a subscription.');
        }
        /** @var ApiResponse $response */
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);
        $response = $this->http->post('/payments/payhere/subscriptions/cancel', array_filter(['subscription_id' => $this->subscriptionId($subscriptionId), 'confirm_cancel' => true, 'gateway_environment' => $environment?->value], static fn (mixed $value): bool => $value !== null), ['Idempotency-Key' => $idempotencyKey]);

        return SubscriptionCommandResult::fromArray($response->data);
    }

    private function subscriptionId(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^\d{1,32}$/', $value)) {
            throw new InvalidArgumentException('subscriptionId must be a PayHere numeric subscription ID.');
        }

        return $value;
    }

    /** @return array<string, string> */
    private function environmentQuery(?GatewayEnvironment $gatewayEnvironment): array
    {
        $environment = GatewayEnvironment::configuredFor(GatewayCode::PayHere, $gatewayEnvironment);

        return $environment === null ? [] : ['gateway_environment' => $environment->value];
    }
}
