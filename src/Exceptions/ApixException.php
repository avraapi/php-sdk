<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Base exception for all APIX SDK errors.
 *
 * All SDK-specific exceptions extend this class, allowing callers to
 * catch the entire exception hierarchy with a single catch block:
 *
 *   try {
 *       $apix->sms()->sendSingle(...);
 *   } catch (\Avraapi\Apix\Exceptions\ApixException $e) {
 *       // handles ALL apix errors
 *   }
 */
class ApixException extends \RuntimeException
{
    /**
     * Machine-readable error code from the APIX gateway error envelope.
     * Examples: 'validation_failed', 'unauthorized', 'rate_limit_exceeded'.
     */
    protected readonly string $errorCode;

    /**
     * The raw request ID returned by the gateway, if available.
     */
    protected readonly ?string $requestId;

    /**
     * The HTTP status code of the failed response.
     */
    protected readonly int $httpStatus;

    /**
     * The full decoded error payload from the APIX gateway.
     *
     * @var array<string, mixed>
     */
    protected readonly array $payload;

    /**
     * @param  array<string, mixed>  $payload  Full decoded JSON error response.
     */
    public function __construct(
        string $message,
        int $httpStatus,
        string $errorCode = 'unknown_error',
        ?string $requestId = null,
        array $payload = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);

        $this->errorCode  = $errorCode;
        $this->requestId  = $requestId;
        $this->httpStatus = $httpStatus;
        $this->payload    = $payload;
    }

    /**
     * Machine-readable error code from the APIX error envelope.
     * Stable across gateway versions — safe to match in switch/match blocks.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * APIX request trace ID for debugging. May be null if the error
     * occurred before the gateway assigned an ID (e.g. auth failures).
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * HTTP status code of the failed response.
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /**
     * Full decoded error payload from the gateway response.
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Factory: construct an ApixException (or subclass) from a decoded
     * APIX error response and HTTP status code.
     *
     * This is called by HttpClient after it detects a non-2xx response
     * and decodes the JSON error envelope.
     *
     * @param  array<string, mixed>  $payload  Full decoded JSON error body.
     */
    public static function fromPayload(int $httpStatus, array $payload): static
    {
        $errorCode = (string) ($payload['error']['code'] ?? 'unknown_error');
        $message   = (string) ($payload['error']['message'] ?? 'An unknown error occurred.');
        $requestId = isset($payload['request_id']) && is_string($payload['request_id'])
            ? $payload['request_id']
            : null;

        return new static(
            message:    $message,
            httpStatus: $httpStatus,
            errorCode:  $errorCode,
            requestId:  $requestId,
            payload:    $payload,
        );
    }
}
