<?php

declare(strict_types=1);

namespace Avraapi\Apix\Payments\PayHere;

use Avraapi\Apix\HttpClient;

final class PayHereService
{
    public function __construct(private readonly HttpClient $http) {}

    public function retrieval(): RetrievalService
    {
        return new RetrievalService($this->http);
    }

    public function refunds(): RefundsService
    {
        return new RefundsService($this->http);
    }

    public function recurring(): RecurringService
    {
        return new RecurringService($this->http);
    }

    public function subscriptions(): SubscriptionManagerService
    {
        return new SubscriptionManagerService($this->http);
    }

    public function preapprovals(): PreapprovalsService
    {
        return new PreapprovalsService($this->http);
    }

    public function authorizations(): AuthorizationsService
    {
        return new AuthorizationsService($this->http);
    }

    public function charges(): ChargesService
    {
        return new ChargesService($this->http);
    }

    public function captures(): CapturesService
    {
        return new CapturesService($this->http);
    }
}
