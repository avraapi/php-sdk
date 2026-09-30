<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit\Payments;

use Avraapi\Apix\Config;
use Avraapi\Apix\Exceptions\PaymentAccessException;
use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Payments\PaymentAvailability;
use Avraapi\Apix\Payments\PaymentAvailabilityReason;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PaymentAvailabilityTest extends TestCase
{
    public function test_it_preserves_a_public_availability_payload_for_elements(): void
    {
        $availability = PaymentAvailability::fromArray([
            'ready' => false,
            'reason' => 'upg_entitlement_inactive',
            'message' => 'Payment methods are currently unavailable for this project.',
            'methods' => [],
        ], 'request-123');

        self::assertFalse($availability->isReady());
        self::assertSame(PaymentAvailabilityReason::UpgEntitlementInactive, $availability->reason);
        self::assertSame('request-123', $availability->requestId);
        self::assertSame([
            'ready' => false,
            'reason' => 'upg_entitlement_inactive',
            'message' => 'Payment methods are currently unavailable for this project.',
            'methods' => [],
        ], $availability->toElementsPayload());
    }

    public function test_it_maps_workspace_payment_access_errors_to_a_typed_exception(): void
    {
        $client = new HttpClient(new Config([
            'apiKey' => 'project-key',
            'apiSecret' => 'project-secret',
        ]));
        $mapper = new ReflectionMethod(HttpClient::class, 'mapException');
        $exception = $mapper->invoke($client, 403, [
            'request_id' => 'request-456',
            'error' => [
                'code' => 'upg_entitlement_inactive',
                'message' => 'Payment methods are currently unavailable for this project.',
            ],
        ]);

        self::assertInstanceOf(PaymentAccessException::class, $exception);
        self::assertSame('upg_entitlement_inactive', $exception->getErrorCode());
    }
}
