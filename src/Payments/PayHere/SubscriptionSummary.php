<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

final readonly class SubscriptionSummary
{
    public function __construct(
        public ?string $subscriptionId,
        public ?string $orderId,
        public string $status,
        public ?string $amount,
        public ?string $currency,
        public ?string $recurrence,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['subscription_id']) ? (string) $data['subscription_id'] : null,
            isset($data['order_id']) ? (string) $data['order_id'] : null,
            (string) ($data['status'] ?? 'UNKNOWN'),
            isset($data['amount']) ? (string) $data['amount'] : null,
            isset($data['currency']) ? (string) $data['currency'] : null,
            isset($data['recurrence']) ? (string) $data['recurrence'] : null,
        );
    }
}
