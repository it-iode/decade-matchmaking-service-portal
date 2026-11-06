<?php

namespace App\Domains\Request\Listeners;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Request\Events\RequestExpressInterest;
use App\Domains\Request\Notifications\ExpressInterestNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

readonly class ExpressInterestListener implements ShouldQueue
{
    public function __construct(
        private SystemNotificationService $systemNotificationService
    ) {}

    public function handle(RequestExpressInterest $event): void
    {
        try {
            $request = $event->request;
            $partner = $event->partner;
            $this->systemNotificationService->notifyAdmins(
                'Request Interest Expressed',
                sprintf(
                    'A partner has expressed interest in a request: %s By Partner %s',
                    $request->detail->capacity_development_title ?? $request->id,
                    $partner->name ?? 'Unknown Partner'
                )
            );
            $partner->notify(new ExpressInterestNotification($request));
        } catch (Exception $exception) {
            Log::error('Error during handling partner express interest', [
                'request_id' => $request->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
