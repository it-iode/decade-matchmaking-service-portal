<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Events;

use App\Infrastructure\Email\Models\EmailLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly EmailLog $emailLog
    ) {}
}
