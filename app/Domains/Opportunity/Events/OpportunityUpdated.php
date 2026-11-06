<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Events;

use App\Domains\Opportunity\Models\Opportunity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an opportunity is updated.
 *
 * This event is fired when the opportunity is modified (excluding status
 * and closing_date changes which have their own specific events).
 */
class OpportunityUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Opportunity  $opportunity  The updated opportunity
     */
    public function __construct(
        public readonly Opportunity $opportunity
    ) {}
}
