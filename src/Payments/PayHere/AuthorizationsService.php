<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\AuthorizationOptions;
use Avraapi\Apix\Payments\PaymentSession;
use Avraapi\Apix\Responses\ApiResponse;

final class AuthorizationsService
{
    public function __construct(private readonly HttpClient $http) {}

    public function create(AuthorizationOptions $options): PaymentSession
    { /** @var ApiResponse $response */ $response = $this->http->post('/payments/authorizations', $options->toArray());

        return PaymentSession::fromArray($response->data, $response->requestId);
    }
}
