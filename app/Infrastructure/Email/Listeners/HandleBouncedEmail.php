<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Listeners;

use App\Infrastructure\Email\Events\EmailBounced;
use Illuminate\Support\Facades\Log;

class HandleBouncedEmail
{
    /**
     * Handle the event.
     */
    public function handle(EmailBounced $event): void
    {
        $bounceType = $event->isHardBounce ? 'hard' : 'soft';

        Log::channel('email')->warning('Email bounced', [
            'log_id' => $event->emailLog->id,
            'type' => $bounceType,
            'reason' => $event->reason,
            'recipient' => $event->emailLog->recipient_email,
        ]);

        // For hard bounces, consider marking the email address as invalid
        if ($event->isHardBounce && $event->emailLog->user_id) {
            // TODO: Update user email verification status
            // $user = User::find($event->emailLog->user_id);
            // if ($user) {
            //     $user->markEmailAsInvalid();
            // }
        }
    }
}
