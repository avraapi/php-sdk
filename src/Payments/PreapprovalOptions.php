<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments;

use InvalidArgumentException;

/** Server-only PayHere preapproval request. Browser SDK use is intentionally unsupported. */
final readonly class PreapprovalOptions
{
    /** @param array<string, string> $customer @param array<string, string> $urls */
    public function __construct(public string $orderId, public string $items, public string $currency, public array $customer, public array $urls, public ?string $amount = null, public ?string $merchantDomain = null)
    {
        if (trim($orderId) === '' || trim($items) === '' || ! in_array(strtoupper($currency), ['LKR', 'USD'], true)) {
            throw new InvalidArgumentException('PayHere preapproval requires order ID, items, and LKR or USD currency.');
        }
        if ($amount !== null && (! preg_match('/^(0|[1-9]\d*)(?:\.\d{1,2})?$/', $amount) || preg_match('/^0+(?:\.0{1,2})?$/', $amount))) {
            throw new InvalidArgumentException('Optional preapproval amount must be a positive decimal string.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $order = ['id' => $this->orderId, 'items' => $this->items, 'currency' => strtoupper($this->currency)];
        if ($this->amount !== null) {
            $order['amount'] = $this->amount;
        }

        return ['gateway' => GatewayCode::PayHere->value, 'mode' => CheckoutMode::Redirect->value, 'order' => $order, 'customer' => $this->customer, 'urls' => $this->urls, 'merchant_domain' => $this->merchantDomain];
    }

    public function callbackAmount(): string
    {
        return $this->amount ?? (strtoupper($this->currency) === 'LKR' ? '10.00' : '0.51');
    }
}
