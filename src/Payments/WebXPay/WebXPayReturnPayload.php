<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\WebXPay;

/**
 * Untrusted WebXPay browser return. APIX treats result3ds only as a trigger
 * and independently reconciles it through the Merchant API.
 */
final class WebXPayReturnPayload
{
    /** @param array<string, mixed> $query */
    public function __construct(private readonly array $query) {}

    /** @return array<string, array<string, mixed>> */
    public function toArray(): array
    {
        return ['return' => $this->query];
    }
}
