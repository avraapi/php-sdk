<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

final class CallbackRequest
{
    /** @return array<string, scalar|null> */
    public static function fromGlobals(): array
    {
        return array_filter($_POST, static fn (mixed $value): bool => is_scalar($value) || $value === null);
    }
}
