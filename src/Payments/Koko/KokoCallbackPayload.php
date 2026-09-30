<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\Koko;

use InvalidArgumentException;

/** The exact form fields KOKO posts to the merchant's notification URL. */
final readonly class KokoCallbackPayload
{
    public function __construct(
        public string $orderId,
        public string $transactionId,
        public string $status,
        public string $description,
        public string $signature,
    ) {
        foreach ([$this->orderId, $this->transactionId, $this->status, $this->signature] as $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException('KOKO callback order ID, transaction ID, status, and signature are required.');
            }
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'trnId' => $this->transactionId,
            'status' => $this->status,
            'desc' => $this->description,
            'signature' => $this->signature,
        ];
    }

    /** @param array<string, mixed> $fields */
    public static function fromForm(array $fields): self
    {
        return new self(
            trim((string) ($fields['orderId'] ?? '')),
            trim((string) ($fields['trnId'] ?? '')),
            trim((string) ($fields['status'] ?? '')),
            trim((string) ($fields['desc'] ?? '')),
            trim((string) ($fields['signature'] ?? '')),
        );
    }
}
