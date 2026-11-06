<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Jobs;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Events\EmailFailed;
use App\Infrastructure\Email\Exceptions\MandrillApiException;
use App\Infrastructure\Email\Models\EmailLog;
use App\Infrastructure\Email\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTransactionalEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of attempts
     */
    public int $tries;

    /**
     * Backoff intervals in seconds
     *
     * @var array<int, int>
     */
    public array $backoff;

    /**
     * The maximum number of unhandled exceptions
     */
    public int $maxExceptions = 3;

    /**
     * The recipient User model (when passed as User)
     */
    public ?User $recipient = null;

    /**
     * Recipient data when passed as array (email, name)
     *
     * @var array{email: string, name: string}|null
     */
    public ?array $recipientData = null;

    /**
     * @param  User|array{email: string, name?: string}  $recipient  User model or array with email/name
     * @param  array<string, mixed>  $variables  Template variables
     * @param  array<string, mixed>  $options  Additional options
     */
    public function __construct(
        public string $eventName,
        User|array $recipient,
        public array $variables,
        public array $options = []
    ) {
        // Handle both User model and array recipient
        if ($recipient instanceof User) {
            $this->recipient = $recipient;
            $this->recipientData = null;
        } else {
            $this->recipient = null;
            $this->recipientData = [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? 'User',
            ];
        }
        // Load configuration
        $this->tries = Config::get('mail-templates.queue.max_attempts', 3);
        $this->backoff = Config::get('mail-templates.queue.backoff', [60, 300, 900]);
        // Set queue connection and name
        $this->onConnection(Config::get('mail-templates.queue.connection', 'database'));
        $this->onQueue($options['queue'] ?? Config::get('mail-templates.queue.queue_name', 'emails'));
    }

    /**
     * Get the recipient email address
     */
    public function getRecipientEmail(): string
    {
        return $this->recipient?->email ?? $this->recipientData['email'];
    }

    /**
     * Get the recipient name
     */
    public function getRecipientName(): string
    {
        return $this->recipient?->name ?? $this->recipientData['name'] ?? 'User';
    }

    /**
     * Get the recipient ID (0 for array recipients)
     */
    public function getRecipientId(): int
    {
        return $this->recipient?->id ?? 0;
    }

    /**
     * Get or create a User object for the email service
     * Creates a temporary User when recipient was passed as array
     */
    protected function getRecipientUser(): User
    {
        if ($this->recipient !== null) {
            return $this->recipient;
        }

        // Create a temporary User object for the email service
        $tempUser = new User;
        $tempUser->email = $this->recipientData['email'];
        $tempUser->name = $this->recipientData['name'];
        $tempUser->id = 0;

        return $tempUser;
    }

    /**
     * Execute the job
     *
     * @throws Throwable
     */
    public function handle(EmailTemplateService $emailService): void
    {
        try {
            Log::info('Processing transactional email job', [
                'event' => $this->eventName,
                'recipient' => $this->getRecipientEmail(),
                'attempt' => $this->attempts(),
            ]);

            $result = $emailService->send(
                $this->eventName,
                $this->getRecipientUser(),
                $this->variables,
                $this->options
            );

            if ($result->success) {
                Log::info('Email sent successfully', [
                    'event' => $this->eventName,
                    'recipient' => $this->getRecipientEmail(),
                    'mandrill_id' => $result->mandrillId,
                ]);
            } else {
                Log::warning('Email send failed', [
                    'event' => $this->eventName,
                    'recipient' => $this->getRecipientEmail(),
                    'error' => $result->error,
                ]);

                // If it failed but didn't throw, we might want to retry
                if ($this->attempts() < $this->tries) {
                    $this->release($this->backoff[$this->attempts() - 1] ?? 900);
                }
            }
        } catch (MandrillApiException $e) {
            Log::error('Mandrill API error in email job', [
                'event' => $this->eventName,
                'recipient' => $this->getRecipientEmail(),
                'error' => $e->getMessage(),
                'mandrill_code' => $e->getMandrillCode(),
                'attempt' => $this->attempts(),
            ]);

            // Check if the error is recoverable
            if ($e->isRecoverable() && $this->attempts() < $this->tries) {
                // Calculate backoff
                $delay = $this->backoff[$this->attempts() - 1] ?? 900;

                Log::info('Retrying email job after delay', [
                    'event' => $this->eventName,
                    'recipient' => $this->getRecipientEmail(),
                    'delay' => $delay,
                    'next_attempt' => $this->attempts() + 1,
                ]);

                // Release back to queue with delay
                $this->release($delay);
            } else {
                // Non-recoverable or max attempts reached
                $this->fail($e);
            }
        } catch (Throwable $e) {
            Log::error('Unexpected error in email job', [
                'event' => $this->eventName,
                'recipient' => $this->getRecipientEmail(),
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'attempt' => $this->attempts(),
            ]);

            // Fail the job for unexpected errors
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(?Throwable $exception): void
    {
        // Log the failure with comprehensive details
        Log::channel('email_errors')->error('Email job permanently failed', [
            'event' => $this->eventName,
            'recipient' => $this->getRecipientEmail(),
            'recipient_id' => $this->getRecipientId(),
            'error' => $exception?->getMessage(),
            'error_class' => $exception ? get_class($exception) : null,
            'attempts' => $this->attempts(),
            'variables' => $this->variables,
            'trace' => $exception?->getTraceAsString(),
        ]);

        // Update email log if exists
        $this->updateEmailLogForFailure($exception);

        // Dispatch failure event
        event(new EmailFailed(
            $this->eventName,
            $this->getRecipientUser(),
            $exception?->getMessage() ?? 'Unknown error',
            $this->attempts()
        ));

        // Handle critical emails that must be delivered
        if ($this->isCriticalEmail()) {
            $this->handleCriticalEmailFailure($exception);
        }

        // Track failure metrics for monitoring
        $this->trackFailureMetrics($exception);
    }

    /**
     * Update the email log entry for this failure.
     */
    protected function updateEmailLogForFailure(?Throwable $exception): void
    {
        try {
            // Find the most recent log entry for this email
            // For non-user recipients (id=0), query by null user_id
            $userId = $this->recipient?->id;
            $emailLog = EmailLog::where('user_id', $userId)
                ->where('event_name', $this->eventName)
                ->where('recipient_email', $this->getRecipientEmail())
                ->whereIn('status', [EmailLog::STATUS_QUEUED, EmailLog::STATUS_SENDING])
                ->latest()
                ->first();

            if ($emailLog) {
                $emailLog->updateStatus(
                    EmailLog::STATUS_FAILED,
                    $exception?->getMessage() ?? 'Job failed after maximum attempts'
                );

                // Add failure metadata
                $emailLog->setMetadata('failure_attempts', $this->attempts());
                $emailLog->setMetadata('failure_class', $exception ? get_class($exception) : null);
                $emailLog->setMetadata('failed_at', now()->toIso8601String());
                $emailLog->save();
            }
        } catch (\Exception $e) {
            Log::error('Failed to update email log for failure', [
                'error' => $e->getMessage(),
                'event' => $this->eventName,
                'recipient' => $this->getRecipientEmail(),
            ]);
        }
    }

    /**
     * Check if this is a critical email that requires special handling.
     */
    protected function isCriticalEmail(): bool
    {
        $criticalEvents = Config::get('mail-templates.critical_events', [
            'user.registered',
            'user.email_verified',
            'user.password_reset',
            'request.approved',
            'request.rejected',
        ]);

        return in_array($this->eventName, $criticalEvents, true);
    }

    /**
     * Handle failure for critical emails.
     */
    protected function handleCriticalEmailFailure(?Throwable $exception): void
    {
        // Get admin notification emails
        $adminEmails = Config::get('mail-templates.admin_notifications', []);

        if (empty($adminEmails)) {
            Log::warning('No admin emails configured for critical email failure notifications');

            return;
        }

        // Send admin notification via basic mail (not through our system to avoid loops)
        try {
            $errorDetails = [
                'Event' => $this->eventName,
                'Recipient' => $this->getRecipientEmail(),
                'User ID' => $this->getRecipientId(),
                'Error' => $exception?->getMessage() ?? 'Unknown error',
                'Attempts' => $this->attempts(),
                'Time' => now()->toIso8601String(),
                'Environment' => Config::get('app.env'),
            ];

            $message = "Critical email delivery failure:\n\n";
            foreach ($errorDetails as $key => $value) {
                $message .= "{$key}: {$value}\n";
            }
            $message .= "\nPlease investigate immediately.";

            foreach ($adminEmails as $adminEmail) {
                Mail::raw($message, function ($mail) use ($adminEmail) {
                    $mail->to($adminEmail)
                        ->subject('[URGENT] Critical Email Delivery Failure - '.Config::get('app.name'))
                        ->priority(1); // Highest priority
                });
            }

            Log::info('Admin notification sent for critical email failure', [
                'admins_notified' => count($adminEmails),
                'event' => $this->eventName,
            ]);
        } catch (\Exception $e) {
            Log::critical('Failed to send admin notification for critical email failure', [
                'error' => $e->getMessage(),
                'original_event' => $this->eventName,
                'original_recipient' => $this->getRecipientEmail(),
            ]);
        }
    }

    /**
     * Track failure metrics for monitoring and alerting.
     */
    protected function trackFailureMetrics(?Throwable $exception): void
    {
        try {
            // Store metrics in cache for monitoring dashboards
            $cacheKey = 'email_failures:'.date('Y-m-d:H');
            $failures = cache()->get($cacheKey, []);

            $failures[] = [
                'event' => $this->eventName,
                'recipient' => $this->getRecipientEmail(),
                'error' => $exception?->getMessage(),
                'timestamp' => now()->timestamp,
            ];

            // Keep metrics for 24 hours
            cache()->put($cacheKey, $failures, 86400);

            // Increment failure counter
            $failureCountKey = 'email_failure_count:'.$this->eventName;
            cache()->increment($failureCountKey);

            // Check if we've exceeded failure threshold
            $failureCount = cache()->get($failureCountKey, 0);
            $threshold = Config::get('mail-templates.failure_threshold', 10);

            if ($failureCount >= $threshold) {
                Log::critical('Email failure threshold exceeded', [
                    'event' => $this->eventName,
                    'failure_count' => $failureCount,
                    'threshold' => $threshold,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to track email failure metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        // Job will be retried for up to 1 hour
        return now()->addHour();
    }

    /**
     * Get the tags that should be assigned to the job
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'email',
            'event:'.$this->eventName,
            'user:'.$this->getRecipientId(),
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Get the display name for the job
     */
    public function displayName(): string
    {
        return sprintf(
            'Send %s email to %s',
            $this->eventName,
            $this->getRecipientEmail()
        );
    }
}
