<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\Koko\KokoCallbackPayload;
use Avraapi\Apix\Payments\Koko\KokoReturnPayload;
use PHPUnit\Framework\TestCase;

final class KokoCallbackPayloadTest extends TestCase
{
    public function test_it_preserves_koko_form_field_names_for_server_completion(): void
    {
        $payload = KokoCallbackPayload::fromForm([
            'orderId' => 'ORDER-100',
            'trnId' => 'TRANSACTION-1',
            'status' => 'SUCCESS',
            'desc' => 'Approved',
            'signature' => 'base64-signature',
        ]);

        self::assertSame([
            'orderId' => 'ORDER-100',
            'trnId' => 'TRANSACTION-1',
            'status' => 'SUCCESS',
            'desc' => 'Approved',
            'signature' => 'base64-signature',
        ], $payload->toArray());
    }

    public function test_it_keeps_browser_return_fields_separate_from_signed_callback_fields(): void
    {
        $payload = KokoReturnPayload::fromQuery([
            'orderId' => 'ORDER-100',
            'trnId' => 'TRANSACTION-1',
            'status' => 'CANCELED',
            'unrelated' => 'ignored',
        ]);

        self::assertSame([
            'orderId' => 'ORDER-100',
            'trnId' => 'TRANSACTION-1',
            'status' => 'CANCELED',
        ], $payload->toArray());
    }
}
