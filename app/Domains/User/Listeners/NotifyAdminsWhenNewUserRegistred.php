<?php

declare(strict_types=1);

namespace App\Domains\User\Listeners;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\User\Events\UserRegistered;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for UserRegistered event.
 *
 * Handles:
 * - Sending welcome email to new users
 * - Creating welcome notification
 */
class NotifyAdminsWhenNewUserRegistred implements ShouldQueue
{
    public function __construct(private readonly SystemNotificationService $notificationService) {}

    /**
     * Handle the event.
     *
     * @param  UserRegistered  $event  The user registered event
     */
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        try {
            $this->notificationService->notifyAdmins(
                'New User Registered',
                sprintf(
                    'A new user has registered: %s (ID: %s)',
                    $user->name ?? 'Unknown User',
                    $user->id
                )
            );

        } catch (Exception $e) {
            Log::error('Failed to create admin notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
