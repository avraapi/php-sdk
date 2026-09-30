<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\PaymentSession;
use Avraapi\Apix\Payments\PreapprovalOptions;
use Avraapi\Apix\Responses\ApiResponse;

final class PreapprovalsService
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(PreapprovalOptions $options): PaymentSession
    { /** @var ApiResponse $response */ $response = $this->http->post('/payments/preapprovals', $options->toArray());

        return PaymentSession::fromArray($response->data, $response->requestId);
    }
}
