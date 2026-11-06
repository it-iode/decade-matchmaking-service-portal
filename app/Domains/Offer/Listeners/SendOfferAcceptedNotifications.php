<?php

declare(strict_types=1);

namespace App\Domains\Offer\Listeners;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Offer\Events\OfferAccepted;
use App\Domains\Offer\Notifications\OfferAcceptedNotification;
use App\Domains\User\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for OfferAccepted event.
 *
 * Handles:
 * - Notifying administrators
 * - Notifying the partner who made the offer
 * - Notifying the request owner (confirmation)
 */
class SendOfferAcceptedNotifications implements ShouldQueue
{
    public function __construct(
        private readonly SystemNotificationService $systemNotificationService,
        private readonly UserService $userService
    ) {}

    /**
     * Handle the event.
     *
     * @param  OfferAccepted  $event  The offer accepted event
     */
    public function handle(OfferAccepted $event): void
    {
        $offer = $event->offer;
        $acceptedBy = $event->acceptedBy;

        // Eager load relationships to prevent N+1 queries
        $offer->load(['request.user', 'matchedPartner']);

        $this->systemNotificationService->notifyAdmins(
            'Accepted Offer for a request',
            sprintf(
                'User <span class="font-bold">%s</span> has accepted offer for request <a href="%s" target="_blank" class="font-bold underline">%s</a> ',
                $acceptedBy->name,
                route('admin.request.show', ['id' => $offer->request->id]),
                $offer->request->detail->capacity_development_title
            )
        );
        try {
            $recipients = [];

            // Notify administrators
            $admins = $this->userService->getAllAdmins();
            foreach ($admins as $admin) {
                $recipients[] = [
                    'user' => $admin,
                    'type' => 'admin',
                ];
            }

            // Notify the partner who made the offer
            if ($offer->matchedPartner) {
                $recipients[] = [
                    'user' => $offer->matchedPartner,
                    'type' => 'partner',
                ];
            }

            // Notify the request owner (confirmation)
            if ($acceptedBy) {
                $recipients[] = [
                    'user' => $acceptedBy,
                    'type' => 'requester',
                ];
            }

            // Send emails to all recipients
            foreach ($recipients as $recipient) {
                $recipient['user']->notify(
                    new OfferAcceptedNotification($offer, $acceptedBy, $recipient['type'])
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send offer accepted notifications', [
                'offer_id' => $offer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
