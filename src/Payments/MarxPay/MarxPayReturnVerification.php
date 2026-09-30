<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\MarxPay;

use RuntimeException;

final readonly class MarxPayReturnVerification
{
    public function __construct(public bool $verified, public string $merchantRid, public string $trId, public string $requestId) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        if (($data['verified'] ?? false) !== true || ! is_scalar($data['merchant_rid'] ?? null) || ! is_scalar($data['tr_id'] ?? null)) {
            throw new RuntimeException('APIX returned an invalid MarxPay return verification result.');
        }

        return new self(true, (string) $data['merchant_rid'], (string) $data['tr_id'], $requestId);
    }
}
