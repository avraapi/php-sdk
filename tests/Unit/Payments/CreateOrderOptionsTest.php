<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\CreateOrderOptions;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\GatewayEnvironment;
use PHPUnit\Framework\TestCase;

final class CreateOrderOptionsTest extends TestCase
{
    public function test_it_preserves_decimal_strings_without_float_conversion(): void
    {
        $options = new CreateOrderOptions(
            GatewayCode::PayHere, CheckoutMode::Redirect, 'order-1', 'Test item', '1000.0', 'LKR',
            ['first_name' => 'Saman', 'last_name' => 'Perera', 'email' => 'saman@example.com', 'phone' => '0771234567', 'address' => 'No. 1', 'city' => 'Colombo', 'country' => 'Sri Lanka'],
            ['return_url' => 'https://shop.example.com/return', 'cancel_url' => 'https://shop.example.com/cancel', 'notify_url' => 'https://shop.example.com/notify'],
        );

        self::assertSame('1000.0', $options->toArray()['order']['amount']);
    }

    public function test_it_derives_pay_here_address_from_structured_elements_fields(): void
    {
        $options = new CreateOrderOptions(
            GatewayCode::PayHere, CheckoutMode::Overlay, 'order-2', 'Test item', '150.00', 'LKR',
            [
                'first_name' => 'Kaveesha',
                'last_name' => 'Gimhan',
                'email' => 'founder@example.test',
                'phone' => '+94719594231',
                'address_line_1' => 'Hello World',
                'address_line_2' => 'Ward City',
                'city' => 'Gampaha',
                'country' => 'Sri Lanka',
            ],
            ['return_url' => 'https://shop.example.com/return', 'cancel_url' => 'https://shop.example.com/cancel', 'notify_url' => 'https://shop.example.com/notify'],
        );

        self::assertSame('Hello World, Ward City', $options->toArray()['customer']['address']);
    }

    public function test_it_serializes_an_explicit_gateway_environment_independently_of_api_client_environment(): void
    {
        $options = new CreateOrderOptions(
            gateway: GatewayCode::PayHere,
            mode: CheckoutMode::Redirect,
            orderId: 'order-3',
            items: 'Test item',
            amount: '20.00',
            currency: 'LKR',
            customer: ['first_name' => 'Saman', 'last_name' => 'Perera', 'email' => 'saman@example.com', 'phone' => '0771234567', 'address' => 'No. 1', 'city' => 'Colombo', 'country' => 'Sri Lanka'],
            urls: ['return_url' => 'https://shop.example.com/return', 'cancel_url' => 'https://shop.example.com/cancel', 'notify_url' => 'https://shop.example.com/notify'],
            gatewayEnvironment: GatewayEnvironment::Production,
        );

        self::assertSame('production', $options->toArray()['gateway_environment']);
    }
}
