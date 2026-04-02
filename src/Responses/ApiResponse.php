<?php

declare(strict_types=1);

namespace Avraapi\Apix\Responses;

/**
 * Strongly-typed wrapper for a successful APIX JSON response.
 *
 * Every APIX success envelope has the shape:
 *   {
 *     "success":    true,
 *     "request_id": "uuid-...",
 *     "data":       { ... },
 *     "meta":       { ... }   ← present on some endpoints
 *   }
 *
 * Usage:
 *   $response = $apix->location()->lookupIp('1.1.1.1');
 *   echo $response->data['country'];       // 'Australia'
 *   echo $response->get('data.country');   // dot-notation accessor
 *   echo $response->requestId;             // 'req_...'
 */
final class ApiResponse
{
    /**
     * The raw decoded payload from the APIX gateway.
     *
     * @var array<string, mixed>
     */
    public readonly array $raw;

    /**
     * The `data` key of the response envelope.
     *
     * @var array<string, mixed>
     */
    public readonly array $data;

    /**
     * The `meta` key of the response envelope (may be empty).
     *
     * @var array<string, mixed>
     */
    public readonly array $meta;

    /**
     * APIX request trace ID.
     */
    public readonly string $requestId;

    /**
     * Always true for ApiResponse instances (errors throw exceptions).
     */
    public readonly bool $success;

    /**
     * HTTP status code of the underlying response.
     */
    public readonly int $httpStatus;

    /**
     * @param  array<string, mixed>  $raw        Full decoded JSON body.
     * @param  int                   $httpStatus  HTTP response status code.
     */
    public function __construct(array $raw, int $httpStatus = 200)
    {
        $this->raw        = $raw;
        $this->httpStatus = $httpStatus;
        $this->success    = (bool) ($raw['success'] ?? true);
        $this->requestId  = isset($raw['request_id']) && is_string($raw['request_id'])
            ? $raw['request_id']
            : '';

        $this->data = isset($raw['data']) && is_array($raw['data'])
            ? $raw['data']
            : [];

        $this->meta = isset($raw['meta']) && is_array($raw['meta'])
            ? $raw['meta']
            : [];
    }

    /**
     * Dot-notation accessor for nested response values.
     *
     * Traverses both `data` and the full raw payload.
     *
     * Examples:
     *   $response->get('country')           // from data
     *   $response->get('data.country')      // same
     *   $response->get('meta.provider_override')
     *   $response->get('data.send_method')
     *
     * @param  mixed  $default  Value to return when the key is not found.
     * @return mixed
     */
    public function get(string $dotPath, mixed $default = null): mixed
    {
        $segments = explode('.', $dotPath);

        // Try traversing from root payload first
        $value = $this->dig($this->raw, $segments);
        if ($value !== null) {
            return $value;
        }

        // Then try treating the path as relative to `data`
        $value = $this->dig($this->data, $segments);

        return $value ?? $default;
    }

    /**
     * Check whether a dot-path key exists and is non-null in the response.
     */
    public function has(string $dotPath): bool
    {
        return $this->get($dotPath) !== null;
    }

    /**
     * Return the full data payload as a JSON string.
     *
     * @throws \JsonException
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->raw, $flags | JSON_THROW_ON_ERROR);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Recursively walk $array following the $segments path.
     *
     * @param  array<string, mixed>  $array
     * @param  list<string>          $segments
     */
    private function dig(array $array, array $segments): mixed
    {
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
