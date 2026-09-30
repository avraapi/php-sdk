<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

final readonly class SubscriptionCommandResult
{
    public function __construct(public bool $accepted, public string $providerStatus, public string $subscriptionId) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self((bool) ($data['command_accepted'] ?? false), (string) ($data['provider_status'] ?? 'unknown'), (string) ($data['subscription_id'] ?? ''));
    }
}
