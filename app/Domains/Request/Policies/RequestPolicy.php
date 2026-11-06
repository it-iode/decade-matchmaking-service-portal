<?php

namespace App\Domains\Request\Policies;

use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Policies\OfferPolicy;
use App\Domains\Request\Enums\PublicRequestStatus;
use App\Domains\Request\Models\Request;
use App\Domains\Request\Models\Status;
use App\Domains\User\Models\User;

class RequestPolicy
{
    /**
     * Determine whether the user can view all requests (for admin CSV export).
     */
    public function viewAny(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('administrator');
    }

    /**
     * Determine whether the user can view the request.
     */
    public function view(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        // Owner or admin: full access in any status.
        if ($user->id === $request->user_id || $user->hasRole('administrator')) {
            return true;
        }

        // Other partners may only view a request once its status is publicly
        // visible (validated / offer_made / in_implementation / closed). This
        // keeps drafts, under-review and rejected requests private.
        if ($user->hasRole('partner')
            && PublicRequestStatus::isPubliclyVisible($request->status?->status_code ?? '')) {
            return true;
        }

        // The partner on the active offer keeps access regardless of status
        // (matched partner). activeOffer is eager-loaded by RequestRepository::findById.
        if ($user->id === $request->activeOffer?->matched_partner_id) {
            return true;
        }

        // Subscribers are an intended audience (request.subscribed.show route,
        // viewActiveOffer(), and DetailResource all grant them access). This is
        // the only branch that costs a query, so it runs last.
        return $request->subscribers()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view offers for the request.
     * Individual offers will be filtered based on {@see OfferPolicy::view()}.
     */
    public function viewOffers(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole('administrator')) {
            return true;
        }
        if ($user->id === $request->user_id) {
            return true;
        }

        return $user->hasRole('partner');
    }

    public function viewActiveOffer(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }
        // also allow subscribers
        if ($user->id === $request->user_id || $user->id === $request->activeOffer?->matchedPartner?->id || $request->subscribers()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the request.
     */
    public function update(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        // Only the request owner can edit, and only when in draft status
        return $user->id === $request->user_id
            && $request->status->status_code === Status::DRAFT_STATUS_CODE;
    }

    /**
     * Determine whether the user can delete the request.
     */
    public function delete(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        return $user->id === $request->user_id
            && $request->status->status_code === Status::DRAFT_STATUS_CODE;
    }

    public function manageOffers(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('administrator');
    }

    public function updateStatus(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('administrator');
    }

    public function acceptOffer(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->id !== $request->user_id) {
            return false;
        }

        return $request->offers()
            ->where('status', RequestOfferStatus::ACTIVE)
            ->where('is_accepted', false)
            ->exists();
    }

    public function requestClarifications(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->id === $request->user_id) {
            return $request->offers()
                ->where('status', RequestOfferStatus::ACTIVE)
                ->where('is_accepted', false)
                ->exists();
        }

        return false;
    }

    public function exportPdf(?User $user, Request $request): bool
    {
        // Anyone who can view the request can export it
        return $this->view($user, $request);
    }

    public function expressInterest(?User $user, Request $request): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->id === $request->user->id) {
            return false;
        }

        if ($request->activeOffer?->matchedPartner?->id === $user->id) {
            return false;
        }

        return $user->hasRole('partner')
            && $request->status->status_code === Status::VALIDATED_STATUS_CODE;
    }
}
