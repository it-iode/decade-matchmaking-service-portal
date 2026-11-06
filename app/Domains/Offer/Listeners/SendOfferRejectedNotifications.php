<?php

declare(strict_types=1);

namespace App\Domains\Offer\Listeners;

use App\Domains\Offer\Events\OfferRejected;
use App\Domains\Offer\Notifications\OfferRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for OfferRejected event.
 *
 * Handles:
 * - Notifying the partner who made the offer
 */
class SendOfferRejectedNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  OfferRejected  $event  The offer rejected event
     */
    public function handle(OfferRejected $event): void
    {
        $offer = $event->offer;
        $rejectedBy = $event->rejectedBy;

        try {
            // Notify the partner who made the offer
            if ($offer->matchedPartner) {
                $offer->matchedPartner->notify(new OfferRejectedNotification($offer, $rejectedBy));
            }

            Log::info('Offer rejected notifications sent', [
                'offer_id' => $offer->id,
                'request_id' => $offer->request_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send offer rejected notifications', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
