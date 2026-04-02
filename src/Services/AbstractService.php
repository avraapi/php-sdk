<?php

declare(strict_types=1);

namespace Avraapi\Apix\Services;

use Avraapi\Apix\HttpClient;
use Avraapi\Apix\Responses\ApiResponse;
use Avraapi\Apix\Responses\BinaryResponse;

/**
 * Base class shared by all APIX service groups.
 *
 * Provides the fluent withProvider() method and delegates all HTTP
 * transport to the shared HttpClient instance.
 */
abstract class AbstractService
{
    public function __construct(protected readonly HttpClient $http) {}

    /**
     * Force the APIX gateway to route this request through a specific provider.
     *
     * This injects an `X-Provider-Override` header on the *next* request only.
     * The override is automatically cleared after the request is dispatched.
     *
     * @param  string  $providerCode  The provider's machine-readable code
     *                                (e.g. 'quicksend', 'maxmind', 'apix_qr').
     *
     * @return static  Fluent — returns the same service instance.
     *
     * Example:
     *   $apix->sms()->withProvider('quicksend')->sendSingle('+94771234567', 'Hello!');
     */
    public function withProvider(string $providerCode): static
    {
        $this->http->setProviderOverride(trim($providerCode));
        return $this;
    }

    /**
     * Dispatch a POST request via the shared HTTP client.
     *
     * @param  array<string, mixed>   $payload  JSON request body.
     * @param  array<string, string>  $headers  Additional headers for this request only.
     */
    protected function post(
        string $path,
        array $payload = [],
        array $headers = [],
    ): ApiResponse|BinaryResponse {
        return $this->http->post($path, $payload, $headers);
    }
}
