<?php

declare(strict_types=1);

namespace Avraapi\Apix\Tests\Unit;

use Avraapi\Apix\Config;
use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Services\CurrencyService;
use Avraapi\Apix\Services\LocationService;
use Avraapi\Apix\Services\SecurityService;
use Avraapi\Apix\Services\SmsService;
use Avraapi\Apix\Services\UtilitiesService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PrivacyModeTest extends TestCase
{
    public function test_every_provider_service_can_enable_one_shot_privacy_mode(): void
    {
        $client = new HttpClient(new Config([
            'apiKey' => 'project-key',
            'apiSecret' => 'project-secret',
        ]));

        $buildHeaders = new ReflectionMethod(HttpClient::class, 'buildRequestHeaders');

        foreach ([
            new CurrencyService($client),
            new SecurityService($client),
            new LocationService($client),
            new SmsService($client),
            new UtilitiesService($client),
        ] as $service) {
            self::assertSame($service, $service->withPrivacyMode());

            /** @var array<string, string> $headers */
            $headers = $buildHeaders->invoke($client, []);
            self::assertSame('1', $headers['X-Privacy-Mode']);
        }
    }

    public function test_privacy_mode_is_cleared_after_the_next_request_headers_are_built(): void
    {
        $client = new HttpClient(new Config([
            'apiKey' => 'project-key',
            'apiSecret' => 'project-secret',
        ]));
        $buildHeaders = new ReflectionMethod(HttpClient::class, 'buildRequestHeaders');

        (new SecurityService($client))->withPrivacyMode();

        /** @var array<string, string> $firstHeaders */
        $firstHeaders = $buildHeaders->invoke($client, []);
        /** @var array<string, string> $secondHeaders */
        $secondHeaders = $buildHeaders->invoke($client, []);

        self::assertSame('1', $firstHeaders['X-Privacy-Mode']);
        self::assertArrayNotHasKey('X-Privacy-Mode', $secondHeaders);
    }
}
