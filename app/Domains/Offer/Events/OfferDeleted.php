<?php

declare(strict_types=1);

namespace App\Domains\Offer\Events;

use App\Domains\Offer\Models\Offer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an offer is being deleted.
 *
 * This event is fired before the offer is removed from the database.
 * Listeners can perform cleanup operations or audit logging.
 */
class OfferDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Offer  $offer  The offer being deleted
     */
    public function __construct(
        public readonly Offer $offer
    ) {}
}
