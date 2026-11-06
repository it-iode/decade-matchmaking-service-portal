<?php

declare(strict_types=1);

namespace App\Domains\Request\Listeners;

use App\Domains\Request\Events\RequestPartnerMatched;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for RequestPartnerMatched event.
 *
 * Handles:
 * - Notifying request creator about the match
 * - Notifying the matched partner
 */
class SendRequestPartnerMatchedNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  RequestPartnerMatched  $event  The request partner matched event
     */
    public function handle(RequestPartnerMatched $event): void
    {
        $request = $event->request;
        $partner = $event->partner;

        try {
            $recipients = [];

            // Notify the request creator
            if ($request->user) {
                $recipients[] = [
                    'user' => $request->user,
                    'type' => 'requester',
                ];
            }

            // Notify the matched partner
            $recipients[] = [
                'user' => $partner,
                'type' => 'partner',
            ];

            // Send emails to all recipients
            foreach ($recipients as $recipient) {
                // Deep-link each recipient to the request view they can access:
                // the requester lands on their own request, the partner on the matched view.
                $requestLink = $recipient['type'] === 'requester'
                    ? route('request.me.show', $request->id)
                    : route('request.matched.show', $request->id);

                dispatch(new SendTransactionalEmail(
                    'request.partner.matched',
                    $recipient['user'],
                    [
                        'Request_Title' => $request->detail?->capacity_development_title ?? 'N/A',
                        'Request_Link' => $requestLink,
                        'user_name' => $recipient['user']->name,
                        'Partner_Name' => $partner->name,
                        'Partner_Email' => $partner->email,
                        'recipient_type' => $recipient['type'],
                        'UNSUB' => route('unsubscribe.show', $recipient['user']->id),
                        'UPDATE_PROFILE' => route('notification.preferences.index'),
                    ]
                ));
            }

            Log::info('Request partner matched notifications sent', [
                'request_id' => $request->id,
                'partner_id' => $partner->id,
                'recipient_count' => count($recipients),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send request partner matched notifications', [
                'request_id' => $request->id,
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
