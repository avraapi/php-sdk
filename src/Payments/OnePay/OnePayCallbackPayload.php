<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\OnePay;

/** Represents OnePay's unsigned Dashboard callback as a completion trigger. */
final class OnePayCallbackPayload
{
    /** @param array<string, mixed> $callback */
    public function __construct(private readonly array $callback) {}

    /** @return array<string, array<string, mixed>> */
    public function toArray(): array
    {
        return ['callback' => $this->callback];
    }
}
