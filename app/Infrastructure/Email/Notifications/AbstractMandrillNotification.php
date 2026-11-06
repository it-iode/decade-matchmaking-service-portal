<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Notifications;

use App\Infrastructure\Email\Contracts\MandrillNotification;
use Illuminate\Notifications\Notification;

/**
 * Base class for notifications delivered through the custom Mandrill channel.
 *
 * Concrete notifications declare their template and variables in toMandrill().
 * The notification itself is intentionally NOT queued: MandrillChannel runs
 * synchronously and dispatches a single SendTransactionalEmail job, which is the
 * queued, retryable unit.
 *
 * @see \App\Infrastructure\Email\Channels\MandrillChannel
 */
abstract class AbstractMandrillNotification extends Notification implements MandrillNotification
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mandrill'];
    }

    /**
     * Get the Mandrill representation of the notification.
     *
     * @return array{template: string, variables?: array<string, mixed>, queue?: string}
     */
    abstract public function toMandrill(object $notifiable): array;

    /**
     * Shared unsubscribe / preferences variables, keyed on the recipient.
     *
     * Opt-in: concrete notifications merge this into their variables only when the
     * underlying template expects UNSUB / UPDATE_PROFILE.
     *
     * @return array{UNSUB: string, UPDATE_PROFILE: string}
     */
    protected function baseVariables(object $notifiable): array
    {
        return [
            'UNSUB' => route('unsubscribe.show', $notifiable->id),
            'UPDATE_PROFILE' => route('notification.preferences.index'),
        ];
    }
}
