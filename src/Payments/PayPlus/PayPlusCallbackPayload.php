<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayPlus;

/** Preserves the exact signed PayPlus callback body for server-side verification. */
final readonly class PayPlusCallbackPayload
{
    public function __construct(
        public string $rawBody,
        public string $authorization,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['raw_body' => $this->rawBody, 'authorization' => $this->authorization];
    }
}
