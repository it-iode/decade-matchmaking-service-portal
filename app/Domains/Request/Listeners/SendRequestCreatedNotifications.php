<?php

declare(strict_types=1);

namespace App\Domains\Request\Listeners;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Request\Events\RequestSubmitted;
use App\Domains\Request\Notifications\RequestCreatedNotification;
use App\Domains\User\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for RequestSubmitted event.
 *
 * Handles:
 * - Creating in-app notification for admins
 * - Sending confirmation email to requester
 */
readonly class SendRequestCreatedNotifications implements ShouldQueue
{
    public function __construct(
        private readonly UserService $userService,
        private readonly SystemNotificationService $systemNotificationService
    ) {}

    /**
     * Handle the event.
     *
     * @param  RequestSubmitted  $event  The request created event
     */
    public function handle(RequestSubmitted $event): void
    {
        $request = $event->request;
        try {
            $this->systemNotificationService->notifyAdmins(
                'New Request Submitted',
                sprintf(
                    'A new request has been submitted: %s By %s',
                    $request->capacity_development_title ?? $request->id,
                    $request->user->name ?? 'Unknown User'
                )
            );

            // Send confirmation email to requester
            if ($request->user) {
                $request->user->notify(new RequestCreatedNotification($request));
            }
            Log::info('Request created notifications sent', [
                'request_id' => $request->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send request created notifications', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
