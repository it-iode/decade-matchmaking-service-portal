<?php

declare(strict_types=1);

namespace App\Domains\Offer\Observers;

use App\Domains\Offer\Events\OfferAccepted;
use App\Domains\Offer\Events\OfferCreated;
use App\Domains\Offer\Events\OfferDeleted;
use App\Domains\Offer\Models\Offer;

/**
 * Observer for the Offer model.
 *
 * This observer follows the Single Responsibility Principle:
 * - Only checks conditions and dispatches events
 * - All business logic moved to event listeners
 */
class OfferObserver
{
    /**
     * Handle the Offer "created" event.
     *
     * @param  Offer  $offer  The newly created offer
     */
    public function created(Offer $offer): void
    {
        OfferCreated::dispatch($offer);
    }

    /**
     * Handle the Offer "updated" event.
     *
     * @param  Offer  $offer  The updated offer
     */
    public function updated(Offer $offer): void
    {
        // Check if is_accepted changed from false to true
        if ($offer->isDirty('is_accepted') && $offer->is_accepted && ! $offer->getOriginal('is_accepted')) {
            $acceptedBy = $offer->request->user;
            if ($acceptedBy) {
                OfferAccepted::dispatch($offer, $acceptedBy);
            }
        }
    }

    /**
     * Handle the Offer "deleted" event.
     *
     * @param  Offer  $offer  The offer being deleted
     */
    public function deleted(Offer $offer): void
    {
        OfferDeleted::dispatch($offer);
    }

    /**
     * Handle the Offer "restored" event.
     *
     * @param  Offer  $offer  The restored offer
     */
    public function restored(Offer $offer): void
    {
        // No event needed for restoration currently
    }

    /**
     * Handle the Offer "force deleted" event.
     *
     * @param  Offer  $offer  The force deleted offer
     */
    public function forceDeleted(Offer $offer): void
    {
        // No event needed for force deletion currently
    }
}
