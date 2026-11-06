<?php

declare(strict_types=1);

namespace App\Domains\Request\Events;

use App\Domains\Request\Models\Request;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a request's status changes.
 *
 * This event is fired whenever the status_id field is modified.
 * Listeners should notify the requester, matched partner (if exists),
 * and admins for certain status changes.
 */
class RequestStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request  The request with updated status
     * @param  string|null  $previousStatus  The previous status label (nullable for new requests)
     */
    public function __construct(
        public readonly Request $request,
        public readonly ?string $previousStatus
    ) {}
}
