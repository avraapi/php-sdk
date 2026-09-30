<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\DirectPay;

use InvalidArgumentException;

/**
 * Exact DirectPay callback material for PaymentCompletionOptions.
 *
 * Read the unchanged php://input body and Authorization header in the
 * merchant's server callback. Never construct this from browser data.
 */
final readonly class DirectPayCallbackPayload
{
    public function __construct(
        public string $rawBody,
        public string $authorization,
    ) {
        if (trim($rawBody) === '' || trim($authorization) === '') {
            throw new InvalidArgumentException('DirectPay raw callback body and Authorization header are required.');
        }
    }

    /** @return array{raw_body: string, authorization: string} */
    public function toArray(): array
    {
        return ['raw_body' => $this->rawBody, 'authorization' => $this->authorization];
    }
}
