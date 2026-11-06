<?php

declare(strict_types=1);

namespace App\Domains\Request\Events;

use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a partner is matched to a request.
 *
 * This event is fired when the matched_partner_id field is assigned.
 * Listeners should notify both the requester and the matched partner.
 */
class RequestPartnerMatched
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request  The request with a matched partner
     * @param  User  $partner  The user who was matched as partner
     */
    public function __construct(
        public readonly Request $request,
        public readonly User $partner
    ) {}
}
