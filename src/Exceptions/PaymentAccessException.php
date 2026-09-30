<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * The authenticated project cannot currently access Universal Payment Gateway
 * checkout. This covers slot, entitlement, configuration, and paused-project
 * availability rather than a provider-side payment failure.
 */
final class PaymentAccessException extends ApixException {}
