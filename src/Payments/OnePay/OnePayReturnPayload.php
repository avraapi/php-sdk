<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\OnePay;

/** Represents an untrusted OnePay browser return as a completion trigger. */
final class OnePayReturnPayload
{
    /** @param array<string, mixed> $query */
    public function __construct(private readonly array $query) {}

    /** @return array<string, array<string, mixed>> */
    public function toArray(): array
    {
        return ['return' => $this->query];
    }
}
