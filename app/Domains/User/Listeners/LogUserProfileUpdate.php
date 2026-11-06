<?php

declare(strict_types=1);

namespace App\Domains\User\Listeners;

use App\Domains\User\Events\UserProfileUpdated;
use Illuminate\Support\Facades\Log;

/**
 * Listener for UserProfileUpdated event.
 *
 * Handles:
 * - Logging profile updates for audit trail
 */
class LogUserProfileUpdate
{
    /**
     * Handle the event.
     *
     * @param  UserProfileUpdated  $event  The user profile updated event
     */
    public function handle(UserProfileUpdated $event): void
    {
        Log::info('User profile updated', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'changed_fields' => $event->changedFields,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
