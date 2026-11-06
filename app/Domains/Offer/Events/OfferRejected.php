<?php

declare(strict_types=1);

namespace App\Domains\Offer\Events;

use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an offer is rejected.
 *
 * This event is fired when a request owner rejects a partner's offer.
 * Listeners should notify the partner who made the offer.
 */
class OfferRejected
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Offer  $offer  The rejected offer
     * @param  User  $rejectedBy  The user who rejected the offer
     */
    public function __construct(
        public readonly Offer $offer,
        public readonly User $rejectedBy
    ) {}
}
