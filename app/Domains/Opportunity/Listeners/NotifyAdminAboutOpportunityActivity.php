<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Listeners;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Opportunity\Events\OpportunityClosingDateExtended;
use App\Domains\Opportunity\Events\OpportunityCreated;
use App\Domains\Opportunity\Events\OpportunityDeleted;
use App\Domains\Opportunity\Events\OpportunityStatusChanged;
use App\Domains\Opportunity\Events\OpportunityUpdated;

/**
 * Listener for opportunity activity events.
 *
 * Creates admin notifications for all opportunity lifecycle events:
 * - OpportunityCreated
 * - OpportunityUpdated
 * - OpportunityClosingDateExtended
 * - OpportunityDeleted
 * - OpportunityStatusChanged
 */
class NotifyAdminAboutOpportunityActivity
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly SystemNotificationService $notificationService
    ) {}

    /**
     * Handle opportunity created event.
     */
    public function handleOpportunityCreated(OpportunityCreated $event): void
    {
        $this->notificationService->notifyAdmins(
            'New Opportunity Created',
            sprintf(
                'New opportunity "%s" has been created by %s',
                $event->opportunity->title,
                $event->opportunity->user->name ?? 'Unknown User'
            )
        );
    }

    /**
     * Handle opportunity updated event.
     */
    public function handleOpportunityUpdated(OpportunityUpdated $event): void
    {
        $this->notificationService->notifyAdmins(
            'Opportunity Updated',
            sprintf(
                'Opportunity "%s" has been updated',
                $event->opportunity->title
            )
        );
    }

    /**
     * Handle closing date extended event.
     */
    public function handleClosingDateExtended(OpportunityClosingDateExtended $event): void
    {
        $originalDate = $event->opportunity->getOriginal('closing_date');
        $newDate = $event->opportunity->getAttribute('closing_date');
        $extensionDays = (int) $originalDate->diffInDays($newDate);
        $this->notificationService->notifyAdmins(
            'Opportunity Closing Date Extended',
            sprintf(
                'Opportunity "%s" closing date extended by %d days (new date: %s)',
                $event->opportunity->title,
                $extensionDays,
                $newDate->format('Y-m-d')
            )
        );
    }

    /**
     * Handle opportunity deleted event.
     */
    public function handleOpportunityDeleted(OpportunityDeleted $event): void
    {
        $this->notificationService->notifyAdmins(
            'Opportunity Deleted',
            sprintf(
                'Opportunity "%s" has been deleted',
                $event->opportunity->title
            )
        );
    }

    /**
     * Handle status changed event.
     */
    public function handleStatusChanged(OpportunityStatusChanged $event): void
    {
        $this->notificationService->notifyAdmins(
            'Opportunity Status Changed',
            sprintf(
                'Opportunity "%s" status changed from %s to %s',
                $event->opportunity->title,
                $event->getPreviousStatusEnum()?->label() ?? 'none',
                $event->newStatus->label()
            )
        );
    }
}
