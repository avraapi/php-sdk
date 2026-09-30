<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

final readonly class CreateOrderOptions
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
        public ?string $merchantDomain = null,
        public array $providerOptions = [],
        public ?GatewayEnvironment $gatewayEnvironment = null,
    ) {
        if (trim($orderId) === '' || trim($items) === '' || trim($currency) === '') {
            throw new InvalidArgumentException('Payment order ID, items, and currency are required.');
        }
        if (! preg_match('/^(0|[1-9]\d*)(?:\.\d{1,2})?$/', $amount) || preg_match('/^0+(?:\.0{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Payment amount must be a positive decimal string with at most two decimals.');
        }
        $normalizedCustomer = self::normalizeCustomer($customer);
        foreach (['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'country'] as $field) {
            if (trim((string) ($normalizedCustomer[$field] ?? '')) === '') {
                throw new InvalidArgumentException("Payment customer {$field} is required.");
            }
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $gatewayEnvironment = GatewayEnvironment::configuredFor($this->gateway, $this->gatewayEnvironment);

        return [
            'gateway' => $this->gateway->value,
            // HostedSession is a source-compatible alias. All newly emitted
            // public APIX payment requests use the universal redirect mode.
            'mode' => $this->mode === CheckoutMode::HostedSession ? CheckoutMode::Redirect->value : $this->mode->value,
            ...($gatewayEnvironment === null ? [] : ['gateway_environment' => $gatewayEnvironment->value]),
            'order' => ['id' => $this->orderId, 'items' => $this->items, 'amount' => $this->amount, 'currency' => strtoupper($this->currency)],
            'customer' => self::normalizeCustomer($this->customer),
            'urls' => $this->urls,
            'merchant_domain' => $this->merchantDomain,
            'provider_options' => $this->providerOptions,
        ];
    }

    /** @param array<string, string> $customer @return array<string, string> */
    private static function normalizeCustomer(array $customer): array
    {
        $address = trim((string) ($customer['address'] ?? ''));
        if ($address === '') {
            $address = implode(', ', array_filter([
                trim((string) ($customer['address_line_1'] ?? '')),
                trim((string) ($customer['address_line_2'] ?? '')),
            ], static fn (string $part): bool => $part !== ''));
        }

        return [...$customer, 'address' => $address];
    }
}
