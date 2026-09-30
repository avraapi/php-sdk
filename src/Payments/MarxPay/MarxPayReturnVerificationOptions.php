<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\MarxPay;

use Avraapi\Apix\Payments\GatewayEnvironment;
use InvalidArgumentException;

final readonly class MarxPayReturnVerificationOptions
{
    public function __construct(
        public string $returnedMerchantRid,
        public string $returnedTrId,
        public string $expectedMerchantRid,
        public string $expectedTrId,
        public ?GatewayEnvironment $gatewayEnvironment = null,
    ) {
        foreach ([$returnedMerchantRid, $returnedTrId, $expectedMerchantRid, $expectedTrId] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('Both returned and expected MarxPay merchantRID/trId values are required.');
            }
        }
    }

    /** @return array<string, array<string, string>> */
    public function toArray(): array
    {
        return array_filter([
            'payload' => ['merchantRID' => $this->returnedMerchantRid, 'trId' => $this->returnedTrId],
            'expected' => ['merchant_rid' => $this->expectedMerchantRid, 'tr_id' => $this->expectedTrId],
            'gateway_environment' => $this->gatewayEnvironment?->value,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
