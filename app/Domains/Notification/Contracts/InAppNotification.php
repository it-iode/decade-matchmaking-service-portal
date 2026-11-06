<?php

declare(strict_types=1);

namespace App\Domains\Notification\Contracts;

/**
 * Contract for notifications delivered through the custom in-app "system" channel.
 *
 * Any notification that lists 'system' in its via() method should implement this
 * interface. The channel persists a SystemNotification row from the payload returned
 * by toSystem(). Named InAppNotification to avoid clashing with the
 * App\Domains\Notification\Models\SystemNotification model.
 *
 * @see \App\Domains\Notification\Channels\SystemNotificationChannel
 */
interface InAppNotification
{
    /**
     * Get the in-app representation of the notification.
     *
     * @return array{title: string, description: string}
     */
    public function toSystem(object $notifiable): array;
}
