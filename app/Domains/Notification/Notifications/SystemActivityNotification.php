<?php

declare(strict_types=1);

namespace App\Domains\Notification\Notifications;

use App\Domains\Notification\Contracts\InAppNotification;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app message carrying a title and description, delivered through the
 * custom "system" channel.
 *
 * This is a message object only; it does not select its own audience. Today it is
 * dispatched exclusively via SystemNotificationService::notifyAdmins(), so in
 * practice every send targets all administrators. The class is kept audience-agnostic
 * so a future per-user path (e.g. notifyUser()) can reuse it unchanged.
 *
 * Intentionally NOT ShouldQueue: the row is written synchronously, matching the
 * previous direct SystemNotification::create() behaviour.
 */
class SystemActivityNotification extends Notification implements InAppNotification
{
    public function __construct(
        private readonly string $title,
        private readonly string $description
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['system'];
    }

    /**
     * @return array{title: string, description: string}
     */
    public function toSystem(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
