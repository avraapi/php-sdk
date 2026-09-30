<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

final readonly class RecurringOrderOptions
{
    /** @param array<string, string> $customer @param array<string, string> $urls @param array<string, string> $providerOptions */
    public function __construct(
        public GatewayCode $gateway,
        public CheckoutMode $mode,
        public string $orderId,
        public string $items,
        public string $amount,
        public string $currency,
        public array $customer,
        public array $urls,
        public string $recurrence,
        public string $duration,
        public ?string $startupFee = null,
        public ?string $recurringStartDate = null,
        public ?bool $autoCancel = null,
        public ?int $maxRetries = null,
        public ?bool $isRecoveryDue = null,
        public ?string $merchantDomain = null,
        public array $providerOptions = [],
    ) {
        new CreateOrderOptions($gateway, $mode, $orderId, $items, $amount, $currency, $customer, $urls, $merchantDomain, $providerOptions);
        if (! preg_match('/^[1-9]\d* (?:Week|Month|Year)$/', $recurrence)) {
            throw new InvalidArgumentException('Recurring recurrence must use values such as "1 Month".');
        }
        if ($duration !== 'Forever' && ! preg_match('/^[1-9]\d* (?:Week|Month|Year)$/', $duration)) {
            throw new InvalidArgumentException('Recurring duration must be "Forever" or a value such as "1 Year".');
        }
        if ($maxRetries !== null && ($maxRetries < 0 || $maxRetries > 365)) {
            throw new InvalidArgumentException('maxRetries must be from 0 to 365.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'gateway' => $this->gateway->value,
            'mode' => $this->mode->value,
            'order' => ['id' => $this->orderId, 'items' => $this->items, 'amount' => $this->amount, 'currency' => strtoupper($this->currency)],
            'customer' => $this->customer,
            'urls' => $this->urls,
            'merchant_domain' => $this->merchantDomain,
            'provider_options' => $this->providerOptions,
            'recurring' => array_filter([
                'recurrence' => $this->recurrence,
                'duration' => $this->duration,
                'startup_fee' => $this->startupFee,
                'recurring_start_date' => $this->recurringStartDate,
                'auto_cancel' => $this->autoCancel,
                'max_retries' => $this->maxRetries,
                'is_recovery_due' => $this->isRecoveryDue,
            ], static fn (mixed $value): bool => $value !== null),
        ];
    }
}
