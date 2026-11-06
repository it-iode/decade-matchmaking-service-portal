<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Listeners;

use App\Domains\Opportunity\Events\OpportunityStatusChanged;
use App\Domains\Opportunity\Notifications\OpportunityStatusChangedNotification;

/**
 * Listener for OpportunityStatusChanged event.
 *
 * Handles:
 * - Creating in-app notification for opportunity creator
 * - Sending email to opportunity creator
 */
class SendOpportunityStatusChangedNotifications
{
    /**
     * Handle the event.
     *
     * @param  OpportunityStatusChanged  $event  The opportunity status changed event
     */
    public function handle(OpportunityStatusChanged $event): void
    {
        $opportunity = $event->opportunity;

        try {
            // Send email to opportunity creator
            if ($opportunity->user) {
                $opportunity->user->notify(new OpportunityStatusChangedNotification(
                    $opportunity,
                    $event->previousStatus,
                    $event->newStatus
                ));
            }
        } catch (\Exception $e) {
            return;
        }
    }
}
