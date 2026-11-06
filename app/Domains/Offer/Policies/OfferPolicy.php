<?php

namespace App\Domains\Offer\Policies;

use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;

class OfferPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Administrators and partners can view offers
        return $user->hasRole('administrator') || $user->hasRole('partner');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Offer creator (matched partner), request owner, or admin can view
        return $user->id === $offer->matched_partner_id
            || $user->id === $offer->request->user_id
            || $user->hasRole('administrator');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Partners can create offers
        return $user->hasRole('partner');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('administrator')) {
            return true;
        }

        // Only the offer creator can edit, and only when active and not accepted
        return $user->id === $offer->matched_partner_id
            && $offer->status === RequestOfferStatus::ACTIVE
            && ! $offer->is_accepted;
    }

    public function canEnableOrDisable(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Only administrators can enable or disable offers
        return $user->hasRole('administrator');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Offer creator or admin can delete, only when not accepted
        return ($user->id === $offer->matched_partner_id || $user->hasRole('administrator'))
            && ! $offer->is_accepted;
    }

    /**
     * Determine whether the user can accept the offer.
     */
    public function accept(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Only the request owner can accept offers
        return $user->id === $offer->request->user->id
            && $offer->status === RequestOfferStatus::ACTIVE
            && ! $offer->is_accepted;
    }

    /**
     * Determine whether the user can reject the offer.
     */
    public function reject(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Only the request owner can reject offers
        return $user->id === $offer->request->user_id
            && $offer->status === RequestOfferStatus::ACTIVE
            && ! $offer->is_accepted;
    }

    /**
     * Determine whether the user can request clarifications for the offer.
     */
    public function requestClarifications(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Request owner can request clarifications on active offers
        return $user->id === $offer->request->user_id
            && $offer->status === RequestOfferStatus::ACTIVE
            && ! $offer->is_accepted;
    }

    /**
     * Determine whether the user can manage documents for the offer.
     */
    public function manageDocuments(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        // Offer creator can manage documents
        return $user->id === $offer->matched_partner_id;
    }

    public function uploadFinancialBreakDown(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }
        if ($offer->request->status->status_code !== 'in_implementation') {
            return false;
        }

        // Only the request owner can upload financial breakdown
        return $user->id === $offer->matchedPartner->id || $user->id === $offer->request->user_id;
    }

    public function uploadLessonLearned(?User $user, Offer $offer): bool
    {
        if (! $user) {
            return false;
        }

        if ($offer->request->status->status_code !== 'in_implementation') {
            return false;
        }

        // Only the request owner can upload financial breakdown
        return $user->id === $offer->matchedPartner->id || $user->id === $offer->request->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Offer $offer): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Offer $offer): bool
    {
        return $user->hasRole('administrator');
    }
}
