<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Stripe;

/**
 * Exact raw Stripe webhook evidence for merchant-server completion only.
 * The SDK Base64-encodes it so JSON transport cannot transform signed bytes.
 */
final readonly class StripeWebhookPayload
{
    public function __construct(
        public string $rawBody,
        public string $stripeSignature,
    ) {
        if ($this->rawBody === '' || trim($this->stripeSignature) === '') {
            throw new \InvalidArgumentException('Stripe raw webhook body and Stripe-Signature are required.');
        }
    }

    /** @return array<string, array<string, string>> */
    public function toArray(): array
    {
        return ['webhook' => [
            'raw_body_base64' => base64_encode($this->rawBody),
            'stripe_signature' => $this->stripeSignature,
        ]];
    }
}
