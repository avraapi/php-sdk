<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Payments\MarxPay\MarxPayInitiatePaymentOptions;
use Avraapi\Apix\Payments\MarxPay\MarxPayReturnVerificationOptions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MarxPayOptionsTest extends TestCase
{
    public function test_it_serializes_the_merchant_owned_return_binding(): void
    {
        $options = new MarxPayReturnVerificationOptions('ORDER-100', '5057', 'ORDER-100', '5057');

        self::assertSame([
            'payload' => ['merchantRID' => 'ORDER-100', 'trId' => '5057'],
            'expected' => ['merchant_rid' => 'ORDER-100', 'tr_id' => '5057'],
        ], $options->toArray());
    }

    public function test_it_accepts_numeric_and_uuid_transaction_ids_for_initiation(): void
    {
        self::assertSame(['tr_id' => '5057', 'merchant_rid' => 'ORDER-100'], (new MarxPayInitiatePaymentOptions('5057', 'ORDER-100'))->toArray());
        self::assertSame([
            'tr_id' => 'f5a5f044-31ba-4c3b-adfb-01ac4d998d65',
            'merchant_rid' => 'ORDER-100',
        ], (new MarxPayInitiatePaymentOptions('f5a5f044-31ba-4c3b-adfb-01ac4d998d65', 'ORDER-100'))->toArray());
    }

    public function test_it_rejects_an_empty_transaction_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MarxPayInitiatePaymentOptions('', 'ORDER-100');
    }
}
