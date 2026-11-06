<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

/**
 * Exception thrown when request status operations fail.
 *
 * This exception is used when status-related operations encounter errors,
 * such as invalid status codes or illegal status transitions.
 */
class RequestStatusException extends RequestException
{
    /**
     * Create a new exception for an invalid status code.
     *
     * @param  int  $statusCode  The invalid status code
     */
    public static function invalidStatusCode(int $statusCode): self
    {
        return new self(
            message: "Invalid request status code: {$statusCode}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for an illegal status transition.
     *
     * @param  int  $requestId  The ID of the request
     * @param  int  $fromStatus  The current status code
     * @param  int  $toStatus  The attempted new status code
     */
    public static function illegalTransition(int $requestId, int $fromStatus, int $toStatus): self
    {
        return new self(
            message: "Illegal status transition for request {$requestId}: cannot change from {$fromStatus} to {$toStatus}.",
            statusCode: 422
        );
    }

    /**
     * Create a new exception for a missing status.
     *
     * @param  int  $requestId  The ID of the request
     */
    public static function missingStatus(int $requestId): self
    {
        return new self(
            message: "Request {$requestId} has no status assigned.",
            statusCode: 500
        );
    }

    /**
     * Create a new exception for status update failure.
     *
     * @param  int  $requestId  The ID of the request
     * @param  string  $reason  The reason for the failure
     */
    public static function updateFailed(int $requestId, string $reason): self
    {
        return new self(
            message: "Failed to update status for request {$requestId}: {$reason}.",
            statusCode: 500
        );
    }
}
