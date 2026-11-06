<?php

declare(strict_types=1);

namespace App\Domains\Request\Observers;

use App\Domains\Request\Events\RequestDeleted;
use App\Domains\Request\Events\RequestStatusChanged;
use App\Domains\Request\Models\Request;
use App\Domains\Request\Models\Status;

/**
 * Observer for Request model.
 *
 * This observer follows the Single Responsibility Principle:
 * - Only checks conditions and dispatches events
 * - All business logic moved to event listeners
 */
class RequestObserver
{
    /**
     * Handle the Request "created" event.
     *
     * @param  Request  $request  The newly created request
     */
    public function created(Request $request): void {}

    /**
     * Handle the Request "updated" event.
     *
     * @param  Request  $request  The updated request
     */
    public function updated(Request $request): void
    {
        // Check if status has changed
        if ($request->isDirty('status_id')) {
            $previousStatus = $this->getPreviousStatus($request);
            RequestStatusChanged::dispatch($request, $previousStatus);
        }
    }

    /**
     * Handle the Request "deleting" event.
     *
     * @param  Request  $request  The request being deleted
     */
    public function deleting(Request $request): void
    {
        RequestDeleted::dispatch($request);
    }

    /**
     * Get the previous status label.
     *
     * @param  Request  $request  The request with changed status
     * @return string|null The previous status label
     */
    private function getPreviousStatus(Request $request): ?string
    {
        $originalStatusId = $request->getOriginal('status_id');

        if (! $originalStatusId) {
            return null;
        }

        $previousStatus = Status::find($originalStatusId);

        return $previousStatus?->status_label;
    }
}
