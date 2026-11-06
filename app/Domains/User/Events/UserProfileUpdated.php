<?php

declare(strict_types=1);

namespace App\Domains\User\Events;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a user's profile is updated.
 *
 * This event is fired when user profile fields are modified.
 * Listeners can log changes for audit trail purposes.
 */
class UserProfileUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  User  $user  The user with updated profile
     * @param  array<string, mixed>  $changedFields  Array of field names that were changed
     */
    public function __construct(
        public readonly User $user,
        public readonly array $changedFields
    ) {}
}
