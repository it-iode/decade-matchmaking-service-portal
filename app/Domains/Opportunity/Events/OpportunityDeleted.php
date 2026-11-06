<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Events;

use App\Domains\Opportunity\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an opportunity is being deleted.
 *
 * This event is fired before the opportunity is removed from the database.
 * Listeners can perform cleanup operations or audit logging.
 */
class OpportunityDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Opportunity  $opportunity  The opportunity being deleted
     */
    public function __construct(
        public readonly Opportunity $opportunity
    ) {}
}
