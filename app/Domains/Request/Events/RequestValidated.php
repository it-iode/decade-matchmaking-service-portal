<?php

declare(strict_types=1);

namespace App\Domains\Request\Events;

use App\Domains\Request\Models\Request;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a request passes validation/approval.
 *
 * This event triggers instant email notifications to users who have matching
 * subtheme preferences in their notification settings. This is separate from
 * the weekly newsletter system.
 */
class RequestValidated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request  The validated request
     */
    public function __construct(
        public readonly Request $request
    ) {}
}
