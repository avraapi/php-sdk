<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\PaymentSession;
use Avraapi\Apix\Payments\RecurringOrderOptions;
use Avraapi\Apix\Responses\ApiResponse;

final class RecurringService
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(RecurringOrderOptions $options): PaymentSession
    {
        /** @var ApiResponse $response */
        $response = $this->http->post('/payments/recurring/orders', $options->toArray());

        return PaymentSession::fromArray($response->data, $response->requestId);
    }
}
