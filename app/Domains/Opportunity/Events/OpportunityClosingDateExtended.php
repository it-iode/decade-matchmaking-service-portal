<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Events;

use App\Domains\Opportunity\Models\Opportunity;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when an opportunity's closing date is extended.
 *
 * This event is fired when the closing_date field is changed to a later date.
 * Listeners can log this for analytics or notify interested parties.
 */
class OpportunityClosingDateExtended
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Opportunity  $opportunity  The opportunity with extended closing date
     */
    public function __construct(
        public readonly Opportunity $opportunity
    ) {}
}
