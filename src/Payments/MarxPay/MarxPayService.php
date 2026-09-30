<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\MarxPay;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Responses\ApiResponse;

/** Server-side MarxPay v4 API surface. Never call these methods from a browser. */
final class MarxPayService
{
    public function __construct(private readonly HttpClient $http) {}

    public function verifyReturn(MarxPayReturnVerificationOptions $options): MarxPayReturnVerification
    {
        $payload = $options->toArray();
        $environment = GatewayEnvironment::configuredFor(GatewayCode::MarxPay, $options->gatewayEnvironment);
        if ($environment !== null) {
            $payload['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/marxpay/returns/verify', $payload);

        return MarxPayReturnVerification::fromArray($response->data, $response->requestId);
    }

    public function initiatePayment(MarxPayInitiatePaymentOptions $options): MarxPayPaymentResult
    {
        $payload = $options->toArray();
        $environment = GatewayEnvironment::configuredFor(GatewayCode::MarxPay, $options->gatewayEnvironment);
        if ($environment !== null) {
            $payload['gateway_environment'] = $environment->value;
        }
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/marxpay/orders/initiate', $payload);

        return MarxPayPaymentResult::fromArray($response->data, $response->requestId);
    }

    public function retrieveOrderSummary(string $trId, string $merchantRid, ?GatewayEnvironment $gatewayEnvironment = null): MarxPayPaymentResult
    {
        $trId = trim($trId);
        $merchantRid = trim($merchantRid);
        if ($trId === '' || $merchantRid === '') {
            throw new \InvalidArgumentException('A MarxPay trId and merchantRID are required.');
        }
        $environment = GatewayEnvironment::configuredFor(GatewayCode::MarxPay, $gatewayEnvironment);
        /** @var ApiResponse $response */
        $response = $this->http->get('/payments/marxpay/orders/'.rawurlencode($trId).'/summary', array_filter(['merchant_rid' => $merchantRid, 'gateway_environment' => $environment?->value], static fn (?string $value): bool => $value !== null));

        return MarxPayPaymentResult::fromArray($response->data, $response->requestId);
    }
}
