<?php

declare(strict_types=1);

namespace App\Domains\User\Observers;

use App\Domains\User\Events\UserProfileUpdated;
use App\Domains\User\Events\UserRegistered;
use App\Domains\User\Models\User;

/**
 * Observer for User model.
 *
 * This observer follows the Single Responsibility Principle:
 * - Only checks conditions and dispatches events
 * - All business logic moved to event listeners
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  User  $user  The newly created user
     */
    public function created(User $user): void
    {
        // Every user has exactly one notification settings row (opt-out model:
        // defaults to subscribed with no opt-outs). Keeps audience `whereHas`
        // queries free of a "row missing = subscribed" branch.
        $user->notificationSetting()->firstOrCreate([], [
            'email_notifications_enabled' => true,
        ]);

        UserRegistered::dispatch($user);
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  User  $user  The updated user
     */
    public function updated(User $user): void
    {
        // Get changed fields for profile updates
        $changedFields = array_keys($user->getDirty());

        // Filter out sensitive fields and timestamps
        $profileFields = array_diff($changedFields, [
            'password',
            'remember_token',
            'email_verified_at',
            'updated_at',
            'created_at',
        ]);

        // Dispatch profile update event if profile fields changed
        if (! empty($profileFields)) {
            UserProfileUpdated::dispatch($user, $profileFields);
        }

        // Check if roles changed (requires spatie/laravel-permission package)
        // This would typically be handled by the package itself
        // For now, we'll leave this as a placeholder for future implementation
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  User  $user  The user being deleted
     */
    public function deleted(User $user): void
    {
        // No event needed for user deletion currently
        // Could add UserDeleted event in the future if needed
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  User  $user  The restored user
     */
    public function restored(User $user): void
    {
        // No event needed for restoration currently
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  User  $user  The force deleted user
     */
    public function forceDeleted(User $user): void
    {
        // No event needed for force deletion currently
    }
}
