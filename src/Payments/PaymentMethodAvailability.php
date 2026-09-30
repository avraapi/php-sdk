<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

/** A public, browser-safe payment-method entry returned by availability(). */
final class PaymentMethodAvailability
{
    /** @param array<string, mixed> $data */
    private function __construct(private readonly array $data) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $gateway = isset($data['gateway']) && is_scalar($data['gateway']) ? trim((string) $data['gateway']) : '';
        if ($gateway === '') {
            throw new InvalidArgumentException('Payment availability method is missing its gateway code.');
        }

        $data['gateway'] = $gateway;
        $data['available'] = ! array_key_exists('available', $data) || $data['available'] !== false;

        return new self($data);
    }

    public function gateway(): string
    {
        return $this->data['gateway'];
    }

    public function isAvailable(): bool
    {
        return $this->data['available'] === true;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
