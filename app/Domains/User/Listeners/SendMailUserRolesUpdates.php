<?php

namespace App\Domains\User\Listeners;

use App\Domains\User\Events\UserRoleChanged;
use App\Domains\User\Notifications\UserRolesChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for UserRoleChanged event.
 *
 * Handles:
 * - Sending email notifications about role changes
 */
class SendMailUserRolesUpdates implements ShouldQueue
{
    public function handle(UserRoleChanged $event): void
    {
        $event->user->notify(new UserRolesChangedNotification);
    }
}
