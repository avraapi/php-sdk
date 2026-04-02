<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when a network-level error prevents communication with the APIX gateway.
 *
 * This wraps Guzzle's ConnectException, RequestException (with no response),
 * and other transport-layer failures. The gateway never received the request,
 * so no APIX request_id is available.
 *
 * Common causes:
 *   - DNS resolution failure (wrong APIX_BASE_URL in local testing)
 *   - Connection timeout (server unreachable or overloaded)
 *   - TLS handshake failure
 *   - Laravel Sail not running (during local development)
 */
final class ApixNetworkException extends ApixException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct(
            message:    $message,
            httpStatus: 0,
            errorCode:  'network_error',
            requestId:  null,
            payload:    [],
            previous:   $previous,
        );
    }
}
