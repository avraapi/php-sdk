<?php

declare(strict_types=1);

namespace Avraapi\Apix\Exceptions;

/**
 * Thrown when the APIX gateway returns HTTP 422 (validation_error).
 *
 * Exposes field-level validation messages exactly as returned by the
 * APIX error envelope's `error.details` object.
 *
 * Example usage:
 *
 *   try {
 *       $apix->sms()->sendSingle(to: '', message: '');
 *   } catch (ApixValidationException $e) {
 *       foreach ($e->getValidationErrors() as $field => $messages) {
 *           echo "$field: " . implode(', ', $messages) . PHP_EOL;
 *       }
 *   }
 */
final class ApixValidationException extends ApixException
{
    /**
     * Field-level validation messages keyed by field name.
     *
     * @var array<string, list<string>>
     */
    private readonly array $validationErrors;

    /**
     * @param  array<string, mixed>           $payload
     * @param  array<string, list<string>>    $validationErrors
     */
    public function __construct(
        string $message,
        int $httpStatus,
        string $errorCode,
        ?string $requestId,
        array $payload,
        array $validationErrors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $errorCode, $requestId, $payload, $previous);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Field-level validation errors as returned by the APIX gateway.
     *
     * @return array<string, list<string>>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * {@inheritdoc}
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(int $httpStatus, array $payload): static
    {
        $errorCode = (string) ($payload['error']['code'] ?? 'validation_error');
        $message   = (string) ($payload['error']['message'] ?? 'Validation failed.');
        $requestId = isset($payload['request_id']) && is_string($payload['request_id'])
            ? $payload['request_id']
            : null;

        /** @var array<string, list<string>> $details */
        $details = [];
        if (isset($payload['error']['details']) && is_array($payload['error']['details'])) {
            foreach ($payload['error']['details'] as $field => $messages) {
                if (is_string($field) && is_array($messages)) {
                    $details[$field] = array_values(array_filter($messages, 'is_string'));
                }
            }
        }

        return new static(
            message:          $message,
            httpStatus:       $httpStatus,
            errorCode:        $errorCode,
            requestId:        $requestId,
            payload:          $payload,
            validationErrors: $details,
        );
    }
}
