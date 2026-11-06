<?php

declare(strict_types=1);

namespace App\Domains\Request\Services;

use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Logger service for request-related operations.
 *
 * This service provides structured logging for all request operations
 * using the dedicated 'requests' logging channel.
 */
class RequestLogger
{
    private const CHANNEL = 'requests';

    /**
     * Log request creation.
     *
     * @param  Request  $request  The created request
     * @param  User  $user  The user who created the request
     */
    public function logCreated(Request $request, User $user): void
    {
        Log::channel(self::CHANNEL)->info('Request created', [
            'request_id' => $request->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'status_id' => $request->status_id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log request update.
     *
     * @param  Request  $request  The updated request
     * @param  User  $user  The user who updated the request
     * @param  array<string, mixed>  $changedFields  The fields that were changed
     */
    public function logUpdated(Request $request, User $user, array $changedFields = []): void
    {
        Log::channel(self::CHANNEL)->info('Request updated', [
            'request_id' => $request->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'changed_fields' => array_keys($changedFields),
            'status_id' => $request->status_id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log request status change.
     *
     * @param  Request  $request  The request whose status changed
     * @param  int  $oldStatusCode  The previous status code
     * @param  int  $newStatusCode  The new status code
     * @param  User  $user  The user who changed the status
     */
    public function logStatusChanged(
        Request $request,
        int $oldStatusCode,
        int $newStatusCode,
        User $user
    ): void {
        Log::channel(self::CHANNEL)->info('Request status changed', [
            'request_id' => $request->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'old_status' => $oldStatusCode,
            'new_status' => $newStatusCode,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log request deletion.
     *
     * @param  int  $requestId  The ID of the deleted request
     * @param  User  $user  The user who deleted the request
     */
    public function logDeleted(int $requestId, User $user): void
    {
        Log::channel(self::CHANNEL)->info('Request deleted', [
            'request_id' => $requestId,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log authorization failure.
     *
     * @param  string  $action  The action that was attempted
     * @param  int|null  $requestId  The ID of the request (if applicable)
     * @param  User  $user  The user who attempted the action
     */
    public function logAuthorizationFailure(string $action, ?int $requestId, User $user): void
    {
        Log::channel(self::CHANNEL)->warning('Authorization failed', [
            'action' => $action,
            'request_id' => $requestId,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log general error.
     *
     * @param  string  $operation  The operation that failed
     * @param  Throwable  $exception  The exception that was thrown
     * @param  array<string, mixed>  $context  Additional context information
     */
    public function logError(string $operation, Throwable $exception, array $context = []): void
    {
        Log::channel(self::CHANNEL)->error('Request operation failed', [
            'operation' => $operation,
            'error_message' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
