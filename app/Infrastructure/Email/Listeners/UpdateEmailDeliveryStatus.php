<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Listeners;

use App\Infrastructure\Email\Events\EmailDelivered;
use Illuminate\Support\Facades\Log;

class UpdateEmailDeliveryStatus
{
    /**
     * Handle the event.
     */
    public function handle(EmailDelivered $event): void
    {
        Log::channel('email')->info('Email delivered', [
            'log_id' => $event->emailLog->id,
            'event_name' => $event->emailLog->event_name,
            'recipient' => $event->emailLog->recipient_email,
        ]);

        // Additional processing can be added here
        // For example: update user notification preferences, trigger analytics, etc.
    }
}
