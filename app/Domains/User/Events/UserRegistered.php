<?php

declare(strict_types=1);

namespace App\Domains\User\Events;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new user registers.
 *
 * This event is fired after a user successfully completes registration.
 * Listeners should send welcome emails and create initial notifications.
 */
class UserRegistered
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  User  $user  The newly registered user
     */
    public function __construct(
        public readonly User $user
    ) {}
}
