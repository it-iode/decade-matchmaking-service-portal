<?php

declare(strict_types=1);

namespace App\Domains\Offer\Events;

use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferAccepted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Offer $offer,
        public readonly User $acceptedBy
    ) {}
}
