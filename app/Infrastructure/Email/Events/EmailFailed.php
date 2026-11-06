<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Events;

use App\Domains\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailFailed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $eventName,
        public readonly User $recipient,
        public readonly string $errorMessage,
        public readonly int $attempts
    ) {}
}
