<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\WebXPay\WebXPayReturnPayload;
use PHPUnit\Framework\TestCase;

final class WebXPayReturnPayloadTest extends TestCase
{
    public function test_it_nests_the_untrusted_browser_query_under_return(): void
    {
        self::assertSame(
            ['return' => ['result3ds' => 'base64-observation']],
            (new WebXPayReturnPayload(['result3ds' => 'base64-observation']))->toArray(),
        );
    }
}
