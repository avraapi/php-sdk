<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\CheckoutMode;
use Avraapi\Apix\Payments\GatewayCode;
use Avraapi\Apix\Payments\PaymentSession;
use Avraapi\Apix\Payments\RedirectFormRenderer;
use PHPUnit\Framework\TestCase;

final class RedirectFormRendererTest extends TestCase
{
    public function test_it_escapes_every_redirect_field(): void
    {
        $session = new PaymentSession(GatewayCode::PayHere, CheckoutMode::Redirect, 'prepared', null, [
            'type' => 'redirect_form', 'action_url' => 'https://sandbox.payhere.lk/pay/checkout',
            'fields' => ['items' => '<script>alert(1)</script>'],
        ], 'request-1');

        self::assertStringNotContainsString('<script>', RedirectFormRenderer::render($session));
        self::assertStringContainsString('&lt;script&gt;', RedirectFormRenderer::render($session));
    }
}
