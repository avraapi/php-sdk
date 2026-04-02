<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when the APIX gateway returns HTTP 429.
 *
 * You have exceeded the rate limit configured on your project's integration.
 * Implement exponential back-off before retrying.
 */
final class ApixRateLimitException extends ApixException {}
