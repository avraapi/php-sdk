<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Stripe;

/** Untrusted browser return. APIX retrieves the bound Checkout Session. */
final readonly class StripeReturnPayload
{
    public function __construct(public string $sessionId)
    {
        if (! str_starts_with($this->sessionId, 'cs_')) {
            throw new \InvalidArgumentException('A Stripe Checkout Session ID is required.');
        }
    }

    /** @return array<string, array<string, string>> */
    public function toArray(): array
    {
        return ['return' => ['session_id' => $this->sessionId]];
    }
}
