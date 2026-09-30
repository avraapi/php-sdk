<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Koko;

use InvalidArgumentException;

/**
 * Unsigned KOKO browser return. APIX reconciles it through signed orderView;
 * this payload alone is never payment proof.
 */
final readonly class KokoReturnPayload
{
    public function __construct(
        public string $orderId,
        public ?string $transactionId = null,
        public ?string $status = null,
    ) {
        if (trim($this->orderId) === '') {
            throw new InvalidArgumentException('A KOKO return order ID is required.');
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return array_filter([
            'orderId' => $this->orderId,
            'trnId' => $this->transactionId,
            'status' => $this->status,
        ], static fn (?string $value): bool => $value !== null && trim($value) !== '');
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        return new self(
            trim((string) ($query['orderId'] ?? '')),
            self::nullableString($query['trnId'] ?? null),
            self::nullableString($query['status'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
