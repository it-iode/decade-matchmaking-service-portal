<?php

declare(strict_types=1);

namespace App\Domains\Notification\Channels;

use App\Domains\Notification\Models\SystemNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Custom notification channel for in-app (system) notifications.
 *
 * Persists a SystemNotification row (the `notifications` table) for the notifiable
 * from the payload returned by the notification's toSystem() method.
 *
 * Usage in notifications:
 *
 * public function via(object $notifiable): array
 * {
 *     return ['system'];
 * }
 *
 * public function toSystem(object $notifiable): array
 * {
 *     return ['title' => 'Title', 'description' => 'Description'];
 * }
 *
 * @see \App\Domains\Notification\Contracts\InAppNotification
 */
class SystemNotificationChannel
{
    /**
     * Send the given notification as an in-app system notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSystem')) {
            Log::warning('SystemNotificationChannel: Notification does not implement toSystem()', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        $data = $notification->toSystem($notifiable);

        if (! isset($data['title'], $data['description'])) {
            Log::warning('SystemNotificationChannel: Missing title/description in notification data', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        try {
            SystemNotification::create([
                'user_id' => $notifiable->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('SystemNotificationChannel: Failed to create system notification', [
                'notification' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
