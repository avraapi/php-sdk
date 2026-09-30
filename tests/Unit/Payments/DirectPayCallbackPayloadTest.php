<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\DirectPay\DirectPayCallbackPayload;
use PHPUnit\Framework\TestCase;

final class DirectPayCallbackPayloadTest extends TestCase
{
    public function test_it_preserves_the_exact_callback_body_and_authorization_header(): void
    {
        $payload = new DirectPayCallbackPayload('eyJzdGF0dXMiOjIwMH0=', 'hmac '.str_repeat('a', 64));

        self::assertSame([
            'raw_body' => 'eyJzdGF0dXMiOjIwMH0=',
            'authorization' => 'hmac '.str_repeat('a', 64),
        ], $payload->toArray());
    }
}
