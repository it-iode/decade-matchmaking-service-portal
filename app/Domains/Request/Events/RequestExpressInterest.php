<?php

declare(strict_types=1);

namespace App\Domains\Request\Events;

use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a partner expresses interest in a request.
 */
class RequestExpressInterest
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Request  $request  Request
     * @param  User  $partner  The partner expressing interest
     */
    public function __construct(
        public readonly Request $request,
        public readonly User $partner
    ) {}
}
