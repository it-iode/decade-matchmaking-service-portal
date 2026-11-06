<?php

declare(strict_types=1);

namespace App\Domains\Offer\Events;

use App\Domains\Offer\Models\Offer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new offer is created.
 *
 * This event is fired when a partner creates an offer for a request.
 * Listeners should notify relevant parties about the new offer.
 */
class OfferCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Offer  $offer  The newly created offer
     */
    public function __construct(
        public readonly Offer $offer
    ) {}
}
