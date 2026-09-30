<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use PHPUnit\Framework\TestCase;

final class GatewayEnvironmentTest extends TestCase
{
    private array $previous = [];

    protected function setUp(): void
    {
        foreach (['APIX_ENV', 'APIX_PAYMENT_GATEWAY_ENV', 'APIX_PAYMENT_GATEWAY_ENV_OVERRIDES'] as $name) {
            $this->previous[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previous as $name => $value) {
            putenv($value === false ? $name : $name.'='.$value);
        }
    }

    public function test_overrides_win_over_global_and_explicit_server_options_win_over_both(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV=production');
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=marxpay:sandbox,onepay:sandbox');
        self::assertSame(GatewayEnvironment::Sandbox, GatewayEnvironment::configuredFor(GatewayCode::MarxPay));
        self::assertSame(GatewayEnvironment::Production, GatewayEnvironment::configuredFor(GatewayCode::PayHere));
        self::assertSame(GatewayEnvironment::Production, GatewayEnvironment::configuredFor(GatewayCode::PayPlus));
        self::assertSame(GatewayEnvironment::Production, GatewayEnvironment::configuredFor(GatewayCode::MarxPay, GatewayEnvironment::Production));
    }

    public function test_overrides_work_without_global_and_unmatched_gateways_defer_to_vault(): void
    {
        putenv('APIX_ENV=production');
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=marxpay:sandbox');
        self::assertSame(GatewayEnvironment::Sandbox, GatewayEnvironment::configuredFor(GatewayCode::MarxPay));
        self::assertNull(GatewayEnvironment::configuredFor(GatewayCode::PayHere));
    }

    public function test_onepay_uses_a_matching_server_override(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=onepay:sandbox');

        self::assertSame(GatewayEnvironment::Sandbox, GatewayEnvironment::configuredFor(GatewayCode::OnePay));
    }

    public function test_webxpay_uses_a_matching_server_override(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=webxpay:sandbox');

        self::assertSame(GatewayEnvironment::Sandbox, GatewayEnvironment::configuredFor(GatewayCode::WebXPay));
    }

    public function test_stripe_uses_a_matching_server_override(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=stripe:sandbox');

        self::assertSame(GatewayEnvironment::Sandbox, GatewayEnvironment::configuredFor(GatewayCode::Stripe));
    }

    public function test_missing_or_invalid_global_omits_environment_from_the_order(): void
    {
        $options = new CreateOrderOptions(
            GatewayCode::PayHere, CheckoutMode::Redirect, 'order-1', 'Item', '10.00', 'LKR',
            ['first_name' => 'Test', 'last_name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '0771234567', 'address' => '1 Main St', 'city' => 'Colombo', 'country' => 'Sri Lanka'],
            [],
        );
        foreach ([null, '', 'invalid', 'prod'] as $value) {
            putenv($value === null ? 'APIX_PAYMENT_GATEWAY_ENV' : 'APIX_PAYMENT_GATEWAY_ENV='.$value);
            self::assertArrayNotHasKey('gateway_environment', $options->toArray());
        }
        putenv('APIX_PAYMENT_GATEWAY_ENV=sandbox');
        self::assertSame('sandbox', $options->toArray()['gateway_environment']);
    }

    public function test_duplicate_override_fails_before_any_provider_request(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=marxpay:sandbox,marxpay:production');
        $this->expectException(\InvalidArgumentException::class);
        GatewayEnvironment::configuredFor(GatewayCode::MarxPay);
    }

    public function test_invalid_matching_override_does_not_fall_back_to_production(): void
    {
        putenv('APIX_PAYMENT_GATEWAY_ENV=production');
        putenv('APIX_PAYMENT_GATEWAY_ENV_OVERRIDES=marxpay:typo');
        $this->expectException(\InvalidArgumentException::class);
        GatewayEnvironment::configuredFor(GatewayCode::MarxPay);
    }
}
