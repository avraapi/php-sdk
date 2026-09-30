<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

/** Controls the merchant-server response shape for payment completion. */
final readonly class PaymentResponseOptions
{
    /** @param list<string> $include */
    public function __construct(public string $mode = 'short', public array $include = [])
    {
        if (! in_array($this->mode, ['short', 'include', 'full'], true)) {
            throw new InvalidArgumentException('Payment response mode must be short, include, or full.');
        }
        if ($this->mode !== 'include' && $this->include !== []) {
            throw new InvalidArgumentException('Include paths are valid only with include response mode.');
        }
    }

    public static function short(): self
    {
        return new self('short');
    }

    public static function full(): self
    {
        return new self('full');
    }

    /** @param list<string> $paths Exact, operation-qualified provider-native paths. */
    public static function include(array $paths): self
    {
        foreach ($paths as $path) {
            if (! is_string($path) || trim($path) === '') {
                throw new InvalidArgumentException('Payment response include paths must be non-empty strings.');
            }
        }

        return new self('include', array_values($paths));
    }

    /** @return array{mode: string, include?: list<string>} */
    public function toArray(): array
    {
        return $this->mode === 'include' ? ['mode' => $this->mode, 'include' => $this->include] : ['mode' => $this->mode];
    }
}
