<?php

declare(strict_types=1);

namespace App\Domains\Request\Listeners;

use App\Domains\Request\Events\RequestStatusChanged;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener for RequestStatusChanged event.
 *
 * Handles:
 * - Notifying request creator (always)
 * - Notifying matched partner (if exists)
 * - Notifying admins (for specific statuses)
 */
class SendRequestStatusChangedNotifications implements ShouldQueue
{
    public function handle(RequestStatusChanged $event): void
    {
        $request = $event->request;
        $previousStatus = $event->previousStatus;
        if ($previousStatus === 'draft' && $request->status->status_code === 'under_review') {
            // New submission - handled by RequestSubmitted event
            return;
        }
        // Eager load relationships to prevent N+1 queries
        $request->load(['user', 'matchedPartner', 'status']);
        dispatch(new SendTransactionalEmail(
            'request.status.changed.user',
            $request->user,
            [
                'Request_Title' => $request->detail->capacity_development_title,
                'Request_Status' => $request->status->status_label,
                'Link_to_Request' => route('request.me.show', $request->id),
                'UNSUB' => route('unsubscribe.show', $request->user->id),
                'UPDATE_PROFILE' => route('notification.preferences.index'),
            ]
        ));
        // Notify matched partner if exists
        if ($request->activeOffer?->matchedPartner) {
            dispatch(new SendTransactionalEmail(
                'request.status.changed.matched_partner',
                $request->activeOffer->matchedPartner,
                [
                    'Request_Title' => $request->detail->capacity_development_title,
                    'Request_Status' => $request->status->status_label,
                    'Link_to_Matched_Request' => route('request.matched.show', $request->id),
                    'UNSUB' => route('unsubscribe.show', $request->user->id),
                    'UPDATE_PROFILE' => route('notification.preferences.index'),
                ]
            ));
        }
    }
}
