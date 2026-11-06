<?php

declare(strict_types=1);

namespace App\Domains\Request\Events;

use App\Domains\Request\Models\Request;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a request is being deleted.
 *
 * This event is fired before the request is removed from the database.
 * Listeners can perform cleanup operations or audit logging.
 */
class RequestDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request  The request being deleted
     */
    public function __construct(
        public readonly Request $request
    ) {}
}
