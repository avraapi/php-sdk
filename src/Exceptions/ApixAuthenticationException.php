<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when the APIX gateway returns HTTP 401.
 *
 * Causes:
 *   - Missing X-API-KEY or X-API-SECRET headers
 *   - Invalid credentials
 *   - Credentials valid but project is inactive
 *   - Wrong X-ENV value (key exists but not for that environment)
 *
 * Resolution: verify APIX_PROJECT_KEY, APIX_API_SECRET, and APIX_ENV.
 */
final class ApixAuthenticationException extends ApixException {}
