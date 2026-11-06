<?php

namespace App\Domains\Opportunity\Policies;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;

class OpportunityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->user_id === $user->id
            || $user->hasRole('administrator')
            || $opportunity->status === Status::ACTIVE;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('partner');
    }

    /**
     * Determine whether the user can submit an ODC travel support opportunity.
     *
     * Per issue #218, any signed-in user may do so, without a partner role.
     */
    public function createOdcTravelSupport(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('partner') && $opportunity->user->id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('administrator') || $opportunity->user->id === $user->id;
    }

    public function extend(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('partner') && $opportunity->user->id === $user->id;
    }

    public function reject(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('administrator') && $opportunity->status !== Status::REJECTED;
    }

    public function close(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('administrator') && $opportunity->status !== Status::CLOSED;
    }

    public function approve(User $user, Opportunity $opportunity): bool
    {
        return $user->hasRole('administrator') && $opportunity->status !== Status::ACTIVE;
    }

    public function apply(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->status === Status::ACTIVE;
    }
}
