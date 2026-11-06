<?php

declare(strict_types=1);

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Notifications\SystemActivityNotification;
use App\Domains\User\Services\UserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Service for managing system notifications.
 *
 * Handles creation of notifications for administrators and users.
 */
class SystemNotificationService
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * Create notifications for all administrators.
     *
     * @param  string  $title  The notification title
     * @param  string  $description  The notification description
     */
    public function notifyAdmins(string $title, string $description): void
    {
        try {
            Notification::send(
                $this->userService->getAllAdmins(),
                new SystemActivityNotification($title, $description)
            );
        } catch (\Exception $e) {
            Log::error('Failed to create admin notifications', [
                'title' => $title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
