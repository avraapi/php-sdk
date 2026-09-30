<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\PayPlus\PayPlusCallbackPayload;
use PHPUnit\Framework\TestCase;

final class PayPlusCallbackPayloadTest extends TestCase
{
    public function test_it_preserves_the_exact_signed_callback_body_and_authorization(): void
    {
        $payload = new PayPlusCallbackPayload('eyJvcmRlcklkIjoiT1JELTEifQ==', 'hmac abc');

        self::assertSame([
            'raw_body' => 'eyJvcmRlcklkIjoiT1JELTEifQ==',
            'authorization' => 'hmac abc',
        ], $payload->toArray());
    }
}
