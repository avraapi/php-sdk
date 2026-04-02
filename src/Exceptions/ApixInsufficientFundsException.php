<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when the APIX gateway returns HTTP 402.
 *
 * Your APIX wallet does not have sufficient credits to complete the request.
 * Top up your balance in the APIX dashboard before retrying.
 */
final class ApixInsufficientFundsException extends ApixException {}
