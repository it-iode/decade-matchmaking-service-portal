<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

/**
 * Exception thrown when request validation fails.
 *
 * This exception is used when validation rules fail for request data,
 * such as invalid input parameters or business rule violations.
 */
class RequestValidationException extends RequestException
{
    /**
     * Create a new exception for invalid request data.
     *
     * @param  string  $field  The field that failed validation
     * @param  string  $reason  The reason for validation failure
     */
    public static function invalidField(string $field, string $reason): self
    {
        return new self(
            message: "Validation failed for field '{$field}': {$reason}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for missing required data.
     *
     * @param  string  $field  The missing required field
     */
    public static function missingRequiredField(string $field): self
    {
        return new self(
            message: "Missing required field: {$field}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for invalid mode parameter.
     *
     * @param  string  $mode  The invalid mode value
     */
    public static function invalidMode(string $mode): self
    {
        return new self(
            message: "Invalid mode parameter: {$mode}. Expected 'create' or 'update'.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for business rule violation.
     *
     * @param  string  $rule  The business rule that was violated
     */
    public static function businessRuleViolation(string $rule): self
    {
        return new self(
            message: "Business rule violation: {$rule}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for general validation failure.
     *
     * @param  string  $message  The validation error message
     */
    public static function failed(string $message): self
    {
        return new self(
            message: "Validation failed: {$message}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for invalid data format.
     *
     * @param  string  $field  The field with invalid format
     * @param  string  $expectedFormat  The expected format
     */
    public static function invalidFormat(string $field, string $expectedFormat): self
    {
        return new self(
            message: "Invalid format for field '{$field}'. Expected: {$expectedFormat}.",
            statusCode: 422
        );
    }
}
