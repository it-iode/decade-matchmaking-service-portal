<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

/**
 * Exception thrown when a user lacks authorization to perform a request operation.
 *
 * This exception is used when authorization checks fail for request operations
 * such as viewing, updating, or deleting requests.
 */
class RequestAuthorizationException extends RequestException
{
    /**
     * Create a new exception for unauthorized request viewing.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $userId  The ID of the user attempting the operation
     */
    public static function forView(int $requestId, int $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to view request {$requestId}.",
            statusCode: 403
        );
    }

    /**
     * Create a new exception for unauthorized request updating.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $userId  The ID of the user attempting the operation
     */
    public static function forUpdate(int $requestId, int $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to update request {$requestId}.",
            statusCode: 403
        );
    }

    /**
     * Create a new exception for unauthorized request deletion.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $userId  The ID of the user attempting the operation
     */
    public static function forDeletion(int $requestId, int $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to delete request {$requestId}.",
            statusCode: 403
        );
    }

    /**
     * Create a new exception for unauthorized status change.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $userId  The ID of the user attempting the operation
     */
    public static function forStatusChange(int $requestId, int $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to change status of request {$requestId}.",
            statusCode: 403
        );
    }

    /**
     * Create a new exception for general unauthorized access.
     *
     * @param  string  $action  The action being attempted
     * @param  int  $userId  The ID of the user attempting the operation
     */
    public static function forAction(string $action, int $userId): self
    {
        return new self(
            message: "User {$userId} is not authorized to perform action: {$action}.",
            statusCode: 403
        );
    }
}
