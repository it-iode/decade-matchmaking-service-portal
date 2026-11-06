<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

use Throwable;

/**
 * Exception thrown when database storage operations fail.
 *
 * This exception is used when database operations such as create, update,
 * or delete fail due to database errors or constraints.
 */
class RequestStorageException extends RequestException
{
    /**
     * Create a new exception for failed request creation.
     *
     * @param  Throwable|null  $previous  The previous exception for chaining
     */
    public static function failedToCreate(?Throwable $previous = null): self
    {
        return new self(
            message: 'Failed to create request in database.',
            statusCode: 500,
            previous: $previous
        );
    }

    /**
     * Create a new exception for failed request update.
     *
     * @param  int  $requestId  The ID of the request that failed to update
     * @param  Throwable|null  $previous  The previous exception for chaining
     */
    public static function failedToUpdate(int $requestId, ?Throwable $previous = null): self
    {
        return new self(
            message: "Failed to update request with ID {$requestId}.",
            statusCode: 500,
            previous: $previous
        );
    }

    /**
     * Create a new exception for failed request deletion.
     *
     * @param  int  $requestId  The ID of the request that failed to delete
     * @param  Throwable|null  $previous  The previous exception for chaining
     */
    public static function failedToDelete(int $requestId, ?Throwable $previous = null): self
    {
        return new self(
            message: "Failed to delete request with ID {$requestId}.",
            statusCode: 500,
            previous: $previous
        );
    }

    /**
     * Create a new exception for failed status update.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $statusCode  The status code that failed to apply
     * @param  Throwable|null  $previous  The previous exception for chaining
     */
    public static function failedToUpdateStatus(int $requestId, int $statusCode, ?Throwable $previous = null): self
    {
        return new self(
            message: "Failed to update status to {$statusCode} for request with ID {$requestId}.",
            statusCode: 500,
            previous: $previous
        );
    }

    /**
     * Create a new exception for transaction failure.
     *
     * @param  string  $operation  The operation that was being performed
     * @param  Throwable|null  $previous  The previous exception for chaining
     */
    public static function transactionFailed(string $operation, ?Throwable $previous = null): self
    {
        return new self(
            message: "Database transaction failed during operation: {$operation}.",
            statusCode: 500,
            previous: $previous
        );
    }
}
