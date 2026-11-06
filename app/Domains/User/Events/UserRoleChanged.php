<?php

declare(strict_types=1);

namespace App\Domains\User\Events;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user's roles are changed.
 *
 * This event is fired when user roles are assigned or removed.
 * Listeners can log role changes for security auditing.
 */
class UserRoleChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  User  $user  The user with changed roles
     */
    public function __construct(
        public readonly User $user
    ) {}
}
