<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Events;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpportunityClicked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Opportunity $opportunity,
        public readonly ?User $user,
        public readonly string $ip,
        public readonly string $userAgent,
        public readonly ?string $referer,
    ) {}
}
