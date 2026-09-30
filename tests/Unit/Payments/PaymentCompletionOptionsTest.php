<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\DirectPay\DirectPayCallbackPayload;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\PaymentCompletionOptions;
use Avraapi\Apix\Payments\PaymentCompletionResult;
use Avraapi\Apix\Payments\PaymentResponseOptions;
use Avraapi\Apix\Payments\Stripe\StripeWebhookPayload;
use PHPUnit\Framework\TestCase;

final class PaymentCompletionOptionsTest extends TestCase
{
    public function test_it_defaults_to_the_compact_merchant_server_response(): void
    {
        $options = new PaymentCompletionOptions(GatewayCode::PayHere, 'signed-context', ['status_code' => '2']);

        self::assertSame('short', $options->toArray()['response']['mode']);
    }

    public function test_it_preserves_exact_native_include_paths(): void
    {
        $options = new PaymentCompletionOptions(
            GatewayCode::MarxPay,
            'signed-context',
            ['merchantRID' => 'ORDER-100', 'trId' => '5057'],
            PaymentResponseOptions::include(['summary.gatewayResponse.sourceOfFunds', 'summary.order.customerMail']),
        );

        self::assertSame([
            'mode' => 'include',
            'include' => ['summary.gatewayResponse.sourceOfFunds', 'summary.order.customerMail'],
        ], $options->toArray()['response']);
    }

    public function test_it_maps_full_native_operations_without_changing_their_shape(): void
    {
        $native = [
            'summary' => [
                'status' => 0,
                'data' => ['gatewayResponse' => ['sourceOfFunds' => ['type' => 'CARD']]],
            ],
        ];
        $result = PaymentCompletionResult::fromArray([
            'gateway' => 'marxpay',
            'verified' => true,
            'payment_status' => 'succeeded',
            'provider_status' => 'SUCCESS',
            'native_operations' => $native,
        ], 'request-1');

        self::assertSame($native, $result->nativeOperations);
    }

    public function test_it_can_carry_direct_pay_raw_callback_material_for_server_completion(): void
    {
        $payload = new DirectPayCallbackPayload('{"status":200}', 'hmac '.str_repeat('a', 64));
        $options = new PaymentCompletionOptions(GatewayCode::DirectPay, 'signed-context', $payload->toArray());

        self::assertSame('{"status":200}', $options->toArray()['payload']['raw_body']);
    }

    public function test_it_reconciles_with_an_available_provider_status_endpoint_by_default(): void
    {
        $default = new PaymentCompletionOptions(GatewayCode::Koko, 'signed-context', ['orderId' => 'ORDER-100']);
        $skipped = new PaymentCompletionOptions(GatewayCode::Koko, 'signed-context', ['orderId' => 'ORDER-100'], reconcileProvider: false);

        self::assertTrue($default->toArray()['reconcile_provider']);
        self::assertFalse($skipped->toArray()['reconcile_provider']);
    }

    public function test_onepay_keeps_the_reconciliation_preference_for_api_compatibility(): void
    {
        $options = new PaymentCompletionOptions(GatewayCode::OnePay, 'signed-context', ['transaction_id' => 'txn-1'], reconcileProvider: false);

        self::assertFalse($options->toArray()['reconcile_provider']);
    }

    public function test_webxpay_keeps_the_reconciliation_preference_for_api_compatibility(): void
    {
        $options = new PaymentCompletionOptions(GatewayCode::WebXPay, 'signed-context', ['result3ds' => 'opaque'], reconcileProvider: false);

        self::assertFalse($options->toArray()['reconcile_provider']);
    }

    public function test_stripe_transports_the_exact_webhook_body_as_base64(): void
    {
        $payload = new StripeWebhookPayload('{"id":"evt_123"}', 't=1,v1=signature');
        $options = new PaymentCompletionOptions(GatewayCode::Stripe, 'signed-context', $payload->toArray(), reconcileProvider: false);

        self::assertSame('{"id":"evt_123"}', base64_decode($options->toArray()['payload']['webhook']['raw_body_base64'], true));
        self::assertFalse($options->toArray()['reconcile_provider']);
    }

    public function test_it_maps_safe_reconciliation_metadata(): void
    {
        $result = PaymentCompletionResult::fromArray([
            'gateway' => 'payplus',
            'verified' => true,
            'payment_status' => 'succeeded',
            'provider_status' => 'SUCCESS',
            'reconciliation' => [
                'attempted' => true,
                'state' => 'matched',
                'authority' => 'provider_status',
                'callback_status' => 'SUCCESS',
                'secondary_status' => 'SUCCESS',
                'retry_recommended' => false,
            ],
        ], 'request-2');

        self::assertSame('matched', $result->reconciliation['state']);
        self::assertSame('provider_status', $result->reconciliation['authority']);
    }
}
