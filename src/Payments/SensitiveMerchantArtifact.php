<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use LogicException;

/** A non-serializable sensitive PayHere token. Persist it only in the merchant's own secure vault, or delete it. */
final readonly class SensitiveMerchantArtifact
{
    public function __construct(public string $kind, public string $value) {}

    public function __serialize(): array
    {
        throw new LogicException('Sensitive merchant artifacts must not be serialized.');
    }

    public function __unserialize(array $data): void
    {
        throw new LogicException('Sensitive merchant artifacts must not be unserialized.');
    }
}
