<?php

declare(strict_types=1);

namespace App\Domains\Offer\Listeners;

use App\Domains\Offer\Events\OfferCreated;
use App\Domains\Offer\Notifications\OfferCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for OfferCreated event.
 *
 * Handles:
 * - Notifying request owner about new offer
 * - Notifying admins (optional)
 */
class SendOfferCreatedNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  OfferCreated  $event  The offer created event
     */
    public function handle(OfferCreated $event): void
    {
        $offer = $event->offer;

        // Eager load relationships to prevent N+1 queries
        $offer->load(['request.user', 'matchedPartner']);

        try {
            // Notify request owner
            if ($offer->request && $offer->request->user) {
                $offer->request->user->notify(new OfferCreatedNotification($offer));
            }

            Log::info('Offer created notifications sent', [
                'offer_id' => $offer->id,
                'request_id' => $offer->request_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send offer created notifications', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
