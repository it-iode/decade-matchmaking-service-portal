<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Http;

use App\Infrastructure\Email\Events\EmailBounced;
use App\Infrastructure\Email\Events\EmailClicked;
use App\Infrastructure\Email\Events\EmailDelivered;
use App\Infrastructure\Email\Events\EmailOpened;
use App\Infrastructure\Email\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Handles Mandrill webhook events for email tracking
 *
 * @see https://mailchimp.com/developer/transactional/guides/track-email-status-with-webhooks/
 */
class MandrillWebhookController
{
    /**
     * Handle incoming Mandrill webhook
     */
    public function handle(Request $request): Response
    {
        // Verify webhook signature if configured
        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('Invalid Mandrill webhook signature', [
                'ip' => $request->ip(),
                'signature' => $request->header('X-Mandrill-Signature'),
            ]);

            return response('Invalid signature', 403);
        }

        // Parse webhook events
        $events = $this->parseWebhookEvents($request);

        if (empty($events)) {
            Log::warning('Empty or invalid Mandrill webhook payload');

            return response('Invalid payload', 400);
        }

        // Process each event
        $processed = 0;
        $errors = 0;

        foreach ($events as $event) {
            try {
                $this->processWebhookEvent($event);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Error processing Mandrill webhook event', [
                    'error' => $e->getMessage(),
                    'event_type' => $event['event'] ?? 'unknown',
                    'message_id' => $event['_id'] ?? null,
                ]);
            }
        }

        Log::info('Mandrill webhook processed', [
            'total_events' => count($events),
            'processed' => $processed,
            'errors' => $errors,
        ]);

        return response('OK', 200);
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $webhookKey = Config::get('mail-templates.mandrill.webhook_key');

        // If no webhook key is configured, skip verification (development mode)
        if (empty($webhookKey)) {
            if (Config::get('app.env') === 'production') {
                Log::warning('Mandrill webhook key not configured in production');
            }

            return true;
        }

        $signature = $request->header('X-Mandrill-Signature');

        if (empty($signature)) {
            return false;
        }

        // Reconstruct the expected signature
        $webhookUrl = $request->fullUrl();
        $params = $request->all();

        // Sort parameters by key
        ksort($params);

        // Build the signature base string
        $signedData = $webhookUrl;
        foreach ($params as $key => $value) {
            $signedData .= $key.$value;
        }

        // Generate expected signature
        $expectedSignature = base64_encode(hash_hmac('sha1', $signedData, $webhookKey, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook events from request
     *
     * @return array<int, array>
     */
    protected function parseWebhookEvents(Request $request): array
    {
        // Mandrill sends events in 'mandrill_events' parameter as JSON
        $eventsJson = $request->input('mandrill_events');

        if (empty($eventsJson)) {
            // Try to get from request body directly (for testing)
            $eventsJson = $request->getContent();
        }

        if (empty($eventsJson)) {
            return [];
        }

        try {
            $events = json_decode($eventsJson, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($events)) {
                return [];
            }

            return $events;
        } catch (\JsonException $e) {
            Log::error('Failed to parse Mandrill webhook JSON', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Process a single webhook event
     *
     * @param  array<string, mixed>  $event
     */
    protected function processWebhookEvent(array $event): void
    {
        $eventType = $event['event'] ?? null;
        $messageId = $event['_id'] ?? null;
        $message = $event['msg'] ?? [];

        if (empty($eventType) || empty($messageId)) {
            Log::warning('Mandrill webhook event missing required fields', [
                'event' => $event,
            ]);

            return;
        }

        // Find the email log by Mandrill ID
        $emailLog = EmailLog::where('mandrill_id', $messageId)->first();

        if (! $emailLog) {
            // Try to find by metadata if message has our custom metadata
            $metadata = $message['metadata'] ?? [];
            if (! empty($metadata['log_id'])) {
                $emailLog = EmailLog::find($metadata['log_id']);
            }
        }

        if (! $emailLog) {
            Log::debug('Email log not found for Mandrill webhook', [
                'mandrill_id' => $messageId,
                'event_type' => $eventType,
            ]);

            return;
        }

        // Process based on event type
        switch ($eventType) {
            case 'send':
                $this->handleSendEvent($emailLog, $event);
                break;

            case 'deferral':
                $this->handleDeferralEvent($emailLog, $event);
                break;

            case 'hard_bounce':
            case 'soft_bounce':
                $this->handleBounceEvent($emailLog, $event, $eventType === 'hard_bounce');
                break;

            case 'open':
                $this->handleOpenEvent($emailLog, $event);
                break;

            case 'click':
                $this->handleClickEvent($emailLog, $event);
                break;

            case 'spam':
            case 'unsub':
                $this->handleSpamOrUnsubEvent($emailLog, $event, $eventType);
                break;

            case 'reject':
                $this->handleRejectEvent($emailLog, $event);
                break;

            default:
                Log::info('Unhandled Mandrill webhook event type', [
                    'event_type' => $eventType,
                    'mandrill_id' => $messageId,
                ]);
        }
    }

    /**
     * Handle send event
     */
    protected function handleSendEvent(EmailLog $emailLog, array $event): void
    {
        $emailLog->updateStatus(EmailLog::STATUS_SENT);

        // Store send timestamp
        if (! empty($event['ts'])) {
            $emailLog->sent_at = \Carbon\Carbon::createFromTimestamp($event['ts']);
            $emailLog->save();
        }

        // Dispatch event
        event(new EmailDelivered($emailLog));

        Log::info('Email marked as sent via webhook', [
            'log_id' => $emailLog->id,
            'mandrill_id' => $emailLog->mandrill_id,
        ]);
    }

    /**
     * Handle deferral event
     */
    protected function handleDeferralEvent(EmailLog $emailLog, array $event): void
    {
        // Email is temporarily deferred (will retry)
        $emailLog->setMetadata('deferred_at', now()->toIso8601String());
        $emailLog->setMetadata('deferral_reason', $event['msg']['diag'] ?? 'Unknown');
        $emailLog->save();

        Log::info('Email deferred via webhook', [
            'log_id' => $emailLog->id,
            'reason' => $event['msg']['diag'] ?? null,
        ]);
    }

    /**
     * Handle bounce event
     */
    protected function handleBounceEvent(EmailLog $emailLog, array $event, bool $isHardBounce): void
    {
        $bounceReason = $event['msg']['bounce_description'] ?? 'Unknown bounce reason';

        $emailLog->updateStatus(EmailLog::STATUS_BOUNCED, $bounceReason);
        $emailLog->setMetadata('bounce_type', $isHardBounce ? 'hard' : 'soft');
        $emailLog->setMetadata('bounce_reason', $bounceReason);
        $emailLog->setMetadata('bounce_diag', $event['msg']['diag'] ?? null);
        $emailLog->save();

        // Dispatch event
        event(new EmailBounced($emailLog, $isHardBounce, $bounceReason));

        Log::warning('Email bounced via webhook', [
            'log_id' => $emailLog->id,
            'type' => $isHardBounce ? 'hard' : 'soft',
            'reason' => $bounceReason,
        ]);
    }

    /**
     * Handle open event
     */
    protected function handleOpenEvent(EmailLog $emailLog, array $event): void
    {
        // Update open status
        if (! $emailLog->opened_at) {
            $emailLog->opened_at = now();
            $emailLog->status = EmailLog::STATUS_OPENED;
        }

        // Increment open count
        $emailLog->open_count = ($emailLog->open_count ?? 0) + 1;

        // Store open metadata
        $opens = $emailLog->getMetadata('opens', []);
        $opens[] = [
            'timestamp' => $event['ts'] ?? time(),
            'ip' => $event['ip'] ?? null,
            'location' => $event['location'] ?? null,
            'user_agent' => $event['user_agent'] ?? null,
        ];
        $emailLog->setMetadata('opens', $opens);

        $emailLog->save();

        // Dispatch event
        event(new EmailOpened($emailLog));

        Log::info('Email opened via webhook', [
            'log_id' => $emailLog->id,
            'open_count' => $emailLog->open_count,
        ]);
    }

    /**
     * Handle click event
     */
    protected function handleClickEvent(EmailLog $emailLog, array $event): void
    {
        // Update click status
        if (! $emailLog->clicked_at) {
            $emailLog->clicked_at = now();
            $emailLog->status = EmailLog::STATUS_CLICKED;
        }

        // Ensure opened status is set (click implies open)
        if (! $emailLog->opened_at) {
            $emailLog->opened_at = now();
        }

        // Increment click count
        $emailLog->click_count = ($emailLog->click_count ?? 0) + 1;

        // Store click metadata
        $clicks = $emailLog->getMetadata('clicks', []);
        $clicks[] = [
            'timestamp' => $event['ts'] ?? time(),
            'url' => $event['url'] ?? null,
            'ip' => $event['ip'] ?? null,
            'location' => $event['location'] ?? null,
            'user_agent' => $event['user_agent'] ?? null,
        ];
        $emailLog->setMetadata('clicks', $clicks);

        $emailLog->save();

        // Dispatch event
        event(new EmailClicked($emailLog, $event['url'] ?? null));

        Log::info('Email link clicked via webhook', [
            'log_id' => $emailLog->id,
            'url' => $event['url'] ?? null,
            'click_count' => $emailLog->click_count,
        ]);
    }

    /**
     * Handle spam or unsubscribe event
     */
    protected function handleSpamOrUnsubEvent(EmailLog $emailLog, array $event, string $type): void
    {
        $status = $type === 'spam' ? EmailLog::STATUS_SPAM : EmailLog::STATUS_REJECTED;
        $reason = $type === 'spam' ? 'Marked as spam' : 'User unsubscribed';

        $emailLog->updateStatus($status, $reason);
        $emailLog->setMetadata($type.'_at', now()->toIso8601String());
        $emailLog->save();

        Log::warning('Email marked as '.$type.' via webhook', [
            'log_id' => $emailLog->id,
            'recipient' => $emailLog->recipient_email,
        ]);

        // TODO: Handle unsubscribe - update user preferences
        if ($type === 'unsub' && $emailLog->user_id) {
            // Update user's email preferences
            // $user = User::find($emailLog->user_id);
            // if ($user) {
            //     $user->unsubscribe();
            // }
        }
    }

    /**
     * Handle reject event
     */
    protected function handleRejectEvent(EmailLog $emailLog, array $event): void
    {
        $rejectReason = $event['msg']['reject']['reason'] ?? 'Unknown';

        $emailLog->updateStatus(EmailLog::STATUS_REJECTED, $rejectReason);
        $emailLog->setMetadata('reject_reason', $rejectReason);
        $emailLog->setMetadata('rejected_at', now()->toIso8601String());
        $emailLog->save();

        Log::error('Email rejected via webhook', [
            'log_id' => $emailLog->id,
            'reason' => $rejectReason,
        ]);
    }
}
