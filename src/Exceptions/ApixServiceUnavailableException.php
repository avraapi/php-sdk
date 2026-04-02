<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when the APIX gateway returns HTTP 503.
 *
 * Most commonly caused by a project owner activating the Kill Switch
 * (project_paused). The credentials are valid — the service is deliberately
 * paused. Retry later or contact your project owner.
 */
final class ApixServiceUnavailableException extends ApixException {}
