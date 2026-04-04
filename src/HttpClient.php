<?php

declare(strict_types=1);

namespace Avraapi\Apix;

use Avraapi\Apix\Exceptions\ApixAuthenticationException;
use Avraapi\Apix\Exceptions\ApixException;
use Avraapi\Apix\Exceptions\ApixInsufficientFundsException;
use Avraapi\Apix\Exceptions\ApixNetworkException;
use Avraapi\Apix\Exceptions\ApixRateLimitException;
use Avraapi\Apix\Exceptions\ApixServiceUnavailableException;
use Avraapi\Apix\Exceptions\ApixValidationException;
use Avraapi\Apix\Responses\ApiResponse;
use Avraapi\Apix\Responses\BinaryResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Internal HTTP transport layer for the APIX PHP SDK.
 *
 * Responsibilities:
 *   1. Build and sign every request with the correct APIX authentication headers.
 *   2. Smart path normalization — strips full URLs or /api/v1 prefixes so
 *      developers can paste any form of the endpoint path without breakage.
 *   3. Detect binary vs JSON response bodies and return the appropriate
 *      response object (BinaryResponse or ApiResponse).
 *   4. Map all HTTP error codes to typed SDK exceptions.
 *
 * This class is internal. Consume it only through ApixClient or a Service.
 *
 * @internal
 */
final class HttpClient
{
    /** MIME types that indicate a binary (non-JSON) response body. */
    private const BINARY_CONTENT_TYPES = [
        'image/png',
        'image/svg+xml',
        'application/pdf',
    ];

    private readonly GuzzleClient $guzzle;

    /**
     * Per-request provider override header value (reset after each request).
     */
    private ?string $providerOverride = null;

    public function __construct(private readonly Config $config)
    {
        $this->guzzle = new GuzzleClient([
            'timeout'         => $config->timeout,
            'connect_timeout' => $config->connectTimeout,
            'http_errors'     => false, // We handle errors manually for rich exceptions
            'headers'         => [
                'Accept'       => 'application/json, image/png, image/svg+xml, application/pdf',
                'Content-Type' => 'application/json',
                'User-Agent'   => 'avraapi/php-sdk/1.0.0 PHP/' . PHP_VERSION,
            ],
        ]);
    }

    // ── Public transport methods ──────────────────────────────────────────────

    /**
     * Execute a POST request and return a typed response.
     *
     * @param  array<string, mixed>  $payload   JSON-serializable request body.
     * @param  array<string, string> $headers   Additional per-request headers.
     *
     * @throws ApixException            On any API-level error.
     * @throws ApixNetworkException     On transport-level failure.
     */
    public function post(
        string $path,
        array $payload = [],
        array $headers = [],
    ): ApiResponse|BinaryResponse {
        $uri          = $this->normalizePath($path);
        $mergedHeaders = array_merge($this->buildAuthHeaders(), $headers);

        if ($this->providerOverride !== null) {
            $mergedHeaders['X-Provider-Override'] = $this->providerOverride;
            $this->providerOverride = null; // Consume after single use
        }

        try {
            $response = $this->guzzle->post($uri, [
                RequestOptions::HEADERS => $mergedHeaders,
                RequestOptions::JSON    => $payload,
            ]);
        } catch (ConnectException $e) {
            throw new ApixNetworkException(
                message:  "Could not connect to APIX gateway at '{$uri}'. " .
                          "Check APIX_BASE_URL and ensure the server is reachable. " .
                          "Original error: " . $e->getMessage(),
                previous: $e,
            );
        } catch (RequestException $e) {
            // RequestException with a response is handled below; without one, it's a network error
            if ($e->hasResponse()) {
                /** @var ResponseInterface $failResponse */
                $failResponse = $e->getResponse();
                return $this->handleResponse($failResponse);
            }
            throw new ApixNetworkException(
                message:  'APIX request failed without a server response: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $this->handleResponse($response);
    }

    /**
     * Set a provider override header for the next request only.
     *
     * Called by the fluent withProvider() chain on Service classes.
     * Automatically cleared after the request is dispatched.
     *
     * @internal
     */
    public function setProviderOverride(string $providerCode): void
    {
        $this->providerOverride = $providerCode;
    }

    // ── Response handling ─────────────────────────────────────────────────────

    /**
     * Inspect the response, detect binary vs JSON, and return the right object.
     *
     * @throws ApixException
     */
    private function handleResponse(ResponseInterface $response): ApiResponse|BinaryResponse
    {
        $status      = $response->getStatusCode();
        $contentType = $this->extractContentType($response);
        $body        = (string) $response->getBody();

        // ── Binary success response ───────────────────────────────────────────
        if ($status >= 200 && $status < 300 && $this->isBinary($contentType)) {
            $requestId = $response->getHeaderLine('X-APIX-Request-ID') ?: null;
            return new BinaryResponse(
                body:        $body,
                contentType: $contentType,
                httpStatus:  $status,
                requestId:   $requestId !== '' ? $requestId : null,
            );
        }

        // ── JSON decode ───────────────────────────────────────────────────────
        $decoded = $this->decodeJson($body, $status);

        // ── Success ───────────────────────────────────────────────────────────
        if ($status >= 200 && $status < 300) {
            return new ApiResponse($decoded, $status);
        }

        // ── Error — map to a typed exception ─────────────────────────────────
        throw $this->mapException($status, $decoded);
    }

    /**
     * Map an HTTP error status + decoded payload to the correct exception class.
     *
     * The mapping exactly mirrors APIX's bootstrap/app.php exception rendering.
     *
     * @param  array<string, mixed>  $payload
     */
    private function mapException(int $httpStatus, array $payload): ApixException
    {
        return match (true) {
            $httpStatus === 401                       => ApixAuthenticationException::fromPayload($httpStatus, $payload),
            $httpStatus === 402                       => ApixInsufficientFundsException::fromPayload($httpStatus, $payload),
            $httpStatus === 422                       => ApixValidationException::fromPayload($httpStatus, $payload),
            $httpStatus === 429                       => ApixRateLimitException::fromPayload($httpStatus, $payload),
            $httpStatus === 503                       => ApixServiceUnavailableException::fromPayload($httpStatus, $payload),
            default                                   => ApixException::fromPayload($httpStatus, $payload),
        };
    }

    // ── Smart path normalization ──────────────────────────────────────────────

    /**
     * Normalize any path format into a full request URI.
     *
     * Accepts all of these forms and resolves to the same canonical URL:
     *   'https://avraapi.com/api/v1/sms/send'
     *   '/api/v1/sms/send'
     *   'api/v1/sms/send'
     *   '/sms/send'
     *   'sms/send'
     *
     * Algorithm:
     *   1. If $path looks like a full URL → strip everything up to and including
     *      the base URL or /api/v1 prefix, leaving only the relative segment.
     *   2. Strip any leading /api/v1 or api/v1 prefix.
     *   3. Strip remaining leading slashes.
     *   4. Append the clean segment to config->baseUrl (which already ends
     *      without a slash).
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path);

        // Step 1 — Strip the configured base URL if present (handles copy-pasted full URLs)
        $baseUrl = $this->config->baseUrl; // e.g. 'https://avraapi.com/api/v1'
        if (str_starts_with($path, $baseUrl)) {
            $path = substr($path, strlen($baseUrl));
        } elseif (preg_match('#^https?://#i', $path)) {
            // Another full URL (e.g. localhost) — strip scheme + host + any /api/v1 prefix
            $path = (string) preg_replace('#^https?://[^/]+#i', '', $path);
        }

        // Step 2 — Strip /api/v1 or api/v1 prefix (in any leading-slash variant)
        $path = (string) preg_replace('#^/?api/v\d+/#i', '', ltrim($path, '/'));

        // Step 3 — Strip remaining leading slashes
        $path = ltrim($path, '/');

        // Step 4 — Build final URI
        return $baseUrl . '/' . $path;
    }

    // ── Header builders ───────────────────────────────────────────────────────

    /**
     * Build the mandatory APIX authentication headers for every request.
     *
     * Headers sent:
     *   X-API-KEY    — Project client ID
     *   X-API-SECRET — Project API secret
     *   X-ENV        — Target environment ('dev' or 'prod')
     *
     * @return array<string, string>
     */
    private function buildAuthHeaders(): array
    {
        return [
            'X-API-KEY'    => $this->config->projectKey,
            'X-API-SECRET' => $this->config->apiSecret,
            'X-ENV'        => $this->config->env,
        ];
    }

    // ── Utility helpers ───────────────────────────────────────────────────────

    /**
     * Extract the base MIME type from a Content-Type header value.
     *
     * 'image/png; charset=...' → 'image/png'
     */
    private function extractContentType(ResponseInterface $response): string
    {
        $header = $response->getHeaderLine('Content-Type');
        return strtolower(trim(explode(';', $header)[0]));
    }

    /**
     * Whether the content type indicates binary (non-JSON) data.
     */
    private function isBinary(string $contentType): bool
    {
        foreach (self::BINARY_CONTENT_TYPES as $binaryType) {
            if (str_contains($contentType, $binaryType)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attempt to JSON-decode a response body.
     *
     * Returns an empty error envelope if the body is not valid JSON
     * (e.g. an unexpected HTML error page from a reverse proxy).
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $body, int $httpStatus): array
    {
        if ($body === '') {
            return [
                'success'    => false,
                'request_id' => null,
                'error'      => [
                    'code'    => 'empty_response',
                    'message' => "The APIX gateway returned an empty body with HTTP {$httpStatus}.",
                ],
            ];
        }

        try {
            $decoded = json_decode($body, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [
                'success'    => false,
                'request_id' => null,
                'error'      => [
                    'code'    => 'invalid_json_response',
                    'message' => "The APIX gateway returned a non-JSON body with HTTP {$httpStatus}. " .
                                 "This may indicate a reverse-proxy error or server misconfiguration.",
                ],
            ];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
