<?php

namespace App\Domains\User\Policies;

use App\Domains\User\Models\User;

class UserPolicy
{
    /**
     * Determine if user can view any users (admin only)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine if user can view specific user details
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine if user can assign roles
     */
    public function assignRoles(User $user, User $model): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine if user can block/unblock users
     */
    public function block(User $user, User $model): bool
    {
        // Admins can block anyone except themselves
        return $user->hasRole('administrator') && $user->id !== $model->id;
    }

    /**
     * Determine if user can delete users
     */
    public function delete(User $user, User $model): bool
    {
        // For now, no one can delete users
        return false;
    }

    /**
     * Determine if user can export user data to CSV
     */
    public function exportUsers(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine if user can invite new users
     */
    public function invite(User $user): bool
    {
        return $user->hasRole('administrator');
    }
}
