<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Listeners;

use App\Infrastructure\Email\Events\EmailFailed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfEmailFailure
{
    /**
     * Handle the event.
     */
    public function handle(EmailFailed $event): void
    {
        Log::channel('email_errors')->error('Email failed event', [
            'event_name' => $event->eventName,
            'recipient' => $event->recipient->email,
            'error' => $event->errorMessage,
            'attempts' => $event->attempts,
        ]);

        // Only notify admins for critical failures
        $criticalEvents = Config::get('mail-templates.critical_events', []);

        if (! in_array($event->eventName, $criticalEvents, true)) {
            return;
        }

        // Send admin notification
        $adminEmails = Config::get('mail-templates.admin_notifications', []);

        foreach ($adminEmails as $adminEmail) {
            Mail::raw(
                "Critical email failed:\n\nEvent: {$event->eventName}\nRecipient: {$event->recipient->email}\nError: {$event->errorMessage}\nAttempts: {$event->attempts}",
                function ($message) use ($adminEmail, $event) {
                    $message->to($adminEmail)
                        ->subject('[ALERT] Critical Email Failure - '.$event->eventName);
                }
            );
        }
    }
}
