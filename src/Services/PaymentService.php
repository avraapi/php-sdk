<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\Elements\ElementsService;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use Avraapi\Apix\Payments\Koko\KokoService;
use Avraapi\Apix\Payments\MarxPay\MarxPayService;
use Avraapi\Apix\Payments\OnePay\OnePayService;
use Avraapi\Apix\Payments\PayHere\PayHereService;
use Avraapi\Apix\Payments\PaymentAvailability;
use Avraapi\Apix\Payments\PaymentCompletionOptions;
use Avraapi\Apix\Payments\PaymentCompletionResult;
use Avraapi\Apix\Payments\PaymentSession;
use Avraapi\Apix\Payments\PayPlus\PayPlusService;
use Avraapi\Apix\Payments\WebXPay\WebXPayService;
use Avraapi\Apix\Payments\SensitiveCallbackOptions;
use Avraapi\Apix\Payments\SensitiveVerificationResult;
use Avraapi\Apix\Payments\VerificationResult;
use Avraapi\Apix\Payments\VerifyCallbackOptions;
use Avraapi\Apix\Responses\ApiResponse;

final class PaymentService extends AbstractService
{
    /** @return list<array<string, mixed>> */
    public function methods(?string $merchantDomain = null): array
    {
        /** @var ApiResponse $response */
        $response = $this->get('/payments/methods', $this->methodDiscoveryQuery($merchantDomain));

        return is_array($response->data['methods'] ?? null) ? $response->data['methods'] : [];
    }

    /**
     * Discover the public payment methods currently usable by this project.
     *
     * This is safe only on the merchant server. Its result intentionally omits
     * all APIX, Vault, Workspace, and provider credentials before it may be
     * embedded into Payment Elements.
     */
    public function availability(?string $merchantDomain = null): PaymentAvailability
    {
        /** @var ApiResponse $response */
        $response = $this->get('/payments/availability', $this->methodDiscoveryQuery($merchantDomain));

        return PaymentAvailability::fromArray($response->data, $response->requestId);
    }

    public function createOrder(CreateOrderOptions $options): PaymentSession
    {
        /** @var ApiResponse $response */
        $response = $this->post('/payments/orders', $options->toArray());

        return PaymentSession::fromArray($response->data, $response->requestId);
    }

    /** Complete a callback/return from the merchant server only. */
    public function completePayment(PaymentCompletionOptions $options): PaymentCompletionResult
    {
        /** @var ApiResponse $response */
        $response = $this->post('/payments/complete', $options->toArray(), ['X-AvraAPI-Completion-Delivery' => 'merchant-server']);

        return PaymentCompletionResult::fromArray($response->data, $response->requestId);
    }

    public function verifyCallback(VerifyCallbackOptions $options): VerificationResult
    {
        /** @var ApiResponse $response */
        $response = $this->post('/payments/callbacks/verify', $options->toArray());

        return VerificationResult::fromArray($response->data, $response->requestId);
    }

    /** Never call from a browser or serialize the returned artifact. */
    public function verifySensitiveCallback(SensitiveCallbackOptions $options): SensitiveVerificationResult
    {
        /** @var ApiResponse $response */
        $response = $this->post('/payments/sensitive/callbacks/verify', $options->toArray(), ['X-AvraAPI-Artifact-Delivery' => 'merchant-server']);

        return SensitiveVerificationResult::fromArray($response->data, $response->requestId);
    }

    /** PayHere Merchant API capabilities; server-side only. */
    public function payhere(): PayHereService
    {
        return new PayHereService($this->http);
    }

    /** MarxPay v4 hosted-session, return-binding, initiation, and summary APIs. */
    public function marxpay(): MarxPayService
    {
        return new MarxPayService($this->http);
    }

    /** PayPlus standard hosted checkout and server-side status reconciliation. */
    public function payplus(): PayPlusService
    {
        return new PayPlusService($this->http);
    }

    /** KOKO signed order-view reconciliation; checkout uses createOrder(). */
    public function koko(): KokoService
    {
        return new KokoService($this->http);
    }

    /** OnePay checkout uses createOrder(); this exposes status reconciliation. */
    public function onepay(): OnePayService
    {
        return new OnePayService($this->http);
    }

    /** WebXPay V2 checkout uses createOrder(); status is Merchant-API backed. */
    public function webxpay(): WebXPayService
    {
        return new WebXPayService($this->http);
    }

    /** Browser mount markup only; never exposes APIX or gateway credentials. */
    public function elements(): ElementsService
    {
        return new ElementsService;
    }

    /**
     * Render the secret-free customer-details mount point for Payment Elements.
     *
     * @param  array<string, mixed>  $styles
     */
    public function renderForm(string $mountId, array $styles = []): string
    {
        return $this->elements()->renderForm($mountId, $styles);
    }

    /**
     * Render the secret-free payment-method mount point for Payment Elements.
     *
     * @param  array<string, mixed>  $styles
     */
    public function renderMethods(string $mountId, array $styles = [], ?string $merchantDomain = null): string
    {
        if (! array_key_exists('methods', $styles) && ! array_key_exists('availability', $styles)) {
            $styles['availability'] = $this->availability($merchantDomain)->toElementsPayload();
        }

        return $this->elements()->renderMethods($mountId, $styles);
    }

    /** @return array<string, string> */
    private function methodDiscoveryQuery(?string $merchantDomain): array
    {
        $query = [];
        foreach (GatewayCode::cases() as $gateway) {
            $environment = GatewayEnvironment::configuredFor($gateway);
            if ($environment !== null) {
                $query['gateway_environments['.$gateway->value.']'] = $environment->value;
            }
        }
        if ($merchantDomain !== null) {
            $query['merchant_domain'] = $merchantDomain;
        }

        return $query;
    }
}
