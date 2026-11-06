<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

/**
 * Exception thrown when a requested resource cannot be found.
 *
 * This exception is used when operations attempt to access a request
 * that does not exist in the database.
 */
class RequestNotFoundException extends RequestException
{
    /**
     * Create a new request not found exception.
     *
     * @param  int  $requestId  The ID of the request that was not found
     */
    public static function forId(int $requestId): self
    {
        return new self(
            message: "Request with ID {$requestId} not found.",
            statusCode: 404
        );
    }

    /**
     * Create a new exception for a missing request during update.
     *
     * @param  int  $requestId  The ID of the request that was not found
     */
    public static function forUpdate(int $requestId): self
    {
        return new self(
            message: "Cannot update request with ID {$requestId}: Request not found.",
            statusCode: 404
        );
    }

    /**
     * Create a new exception for a missing request during deletion.
     *
     * @param  int  $requestId  The ID of the request that was not found
     */
    public static function forDeletion(int $requestId): self
    {
        return new self(
            message: "Cannot delete request with ID {$requestId}: Request not found.",
            statusCode: 404
        );
    }

    /**
     * Create a new exception for a missing request during status change.
     *
     * @param  int  $requestId  The ID of the request that was not found
     */
    public static function forStatusChange(int $requestId): self
    {
        return new self(
            message: "Cannot change status for request with ID {$requestId}: Request not found.",
            statusCode: 404
        );
    }
}
