<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use RuntimeException;

final readonly class SensitiveVerificationResult
{
    public function __construct(public VerificationResult $verification, public SensitiveMerchantArtifact $artifact) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $requestId): self
    {
        $artifact = (array) ($data['artifact'] ?? []);
        if (! is_string($artifact['kind'] ?? null) || ! is_string($artifact['value'] ?? null) || $artifact['value'] === '') {
            throw new RuntimeException('APIX returned no sensitive PayHere artifact.');
        }

        return new self(VerificationResult::fromArray($data, $requestId), new SensitiveMerchantArtifact($artifact['kind'], $artifact['value']));
    }
}
