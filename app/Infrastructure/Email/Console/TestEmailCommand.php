<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Console;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use App\Infrastructure\Email\Services\EmailTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test
                            {event : The email event name (e.g., user.registered)}
                            {email : The recipient email address}
                            {--sync : Send synchronously instead of queueing}
                            {--preview : Preview the email without sending}
                            {--variables=* : Additional variables in key:value format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending a transactional email template';

    public function __construct(
        private readonly EmailTemplateService $emailService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $eventName = $this->argument('event');
        $email = $this->argument('email');
        $isSync = $this->option('sync');
        $isPreview = $this->option('preview');
        $additionalVariables = $this->parseVariables($this->option('variables'));

        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email address: '.$email);

            return Command::FAILURE;
        }

        // Check if template exists
        if (! $this->emailService->templateExists($eventName)) {
            $this->error("Template not found for event: {$eventName}");
            $this->info('Available templates:');

            $templates = $this->emailService->getAvailableTemplates();
            foreach (array_keys($templates) as $name) {
                $this->line("  - {$name}");
            }

            return Command::FAILURE;
        }

        // Find or create test user
        $user = $this->getTestUser($email);

        // Build test variables
        $variables = $this->buildTestVariables($eventName, $user, $additionalVariables);

        // Preview mode
        if ($isPreview) {
            return $this->handlePreview($eventName, $variables);
        }

        // Send the email
        if ($isSync) {
            return $this->sendSynchronously($eventName, $user, $variables);
        } else {
            return $this->sendViaQueue($eventName, $user, $variables);
        }
    }

    /**
     * Handle preview mode
     */
    private function handlePreview(string $eventName, array $variables): int
    {
        $this->info('Email Preview');
        $this->info('=============');

        try {
            $preview = $this->emailService->preview($eventName, $variables);

            $this->info("Event: {$preview['event_name']}");
            $this->info("Template: {$preview['template_name']}");
            $this->info("Subject: {$preview['subject']}");

            $this->newLine();
            $this->info('Required Variables:');
            foreach ($preview['required_variables'] as $var) {
                $value = $preview['variables'][$var] ?? '[MISSING]';
                $this->line("  - {$var}: {$value}");
            }

            if (! empty($preview['optional_variables'])) {
                $this->newLine();
                $this->info('Optional Variables:');
                foreach ($preview['optional_variables'] as $var) {
                    $value = $preview['variables'][$var] ?? '[NOT PROVIDED]';
                    $this->line("  - {$var}: {$value}");
                }
            }

            if (! empty($preview['tags'])) {
                $this->newLine();
                $this->info('Tags: '.implode(', ', $preview['tags']));
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Preview failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Send email synchronously
     */
    private function sendSynchronously(string $eventName, User $user, array $variables): int
    {
        $this->info("Sending email synchronously to {$user->email}...");

        try {
            $result = $this->emailService->send($eventName, $user, $variables);

            if ($result->success) {
                $this->info('Email sent successfully!');
                $this->info("Mandrill ID: {$result->mandrillId}");
                $this->info("Status: {$result->status}");
                $this->info("Log ID: {$result->logId}");

                return Command::SUCCESS;
            } else {
                $this->error('Email failed: '.$result->error);

                return Command::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Send email via queue
     */
    private function sendViaQueue(string $eventName, User $user, array $variables): int
    {
        $this->info("Queueing email to {$user->email}...");

        try {
            dispatch(new SendTransactionalEmail($eventName, $user, $variables));

            $this->info('Email queued successfully!');
            $this->info('Run "php artisan queue:work" to process the queue.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Get or create a test user
     */
    private function getTestUser(string $email): User
    {
        // Try to find existing user (may fail if DB not configured)
        try {
            $user = User::where('email', $email)->first();

            if ($user) {
                $this->info("Using existing user: {$user->name} ({$user->email})");

                return $user;
            }
        } catch (\Throwable $e) {
            // Database not available or other DB error, use temporary user
            $this->warn('Database not available, using temporary test user');
        }

        // Create temporary user object (not saved to DB)
        $user = new User;
        $user->id = 999999;
        $user->email = $email;
        $user->name = 'Test User';

        $this->info("Using temporary test user: {$user->name} ({$user->email})");

        return $user;
    }

    /**
     * Build test variables for the email template
     */
    private function buildTestVariables(string $eventName, User $user, array $additionalVariables): array
    {
        // Default test variables based on event type
        $defaults = match (true) {
            str_starts_with($eventName, 'user.') => [
                'user_name' => $user->name,
                'verification_url' => config('app.url').'/verify/test-token',
                'reset_url' => config('app.url').'/reset/test-token',
                'expires_at' => '24 hours',
                'portal_url' => config('app.url'),
                'role_name' => 'Administrator',
                'block_reason' => 'Test block reason',
                'support_email' => 'support@oceandecade.org',
            ],
            str_starts_with($eventName, 'request.') => [
                'user_name' => $user->name,
                'request_title' => 'Test Ocean Research Request',
                'request_id' => 12345,
                'submission_date' => date('Y-m-d'),
                'request_url' => config('app.url').'/requests/12345',
                'partner_name' => 'Test Partner Organization',
                'partner_organization' => 'Ocean Research Institute',
                'match_url' => config('app.url').'/matches/12345',
                'approved_by' => 'Admin User',
                'approval_message' => 'Your request has been approved for funding.',
            ],
            str_starts_with($eventName, 'offer.') => [
                'recipient_name' => $user->name,
                'partner_organization' => 'Ocean Research Institute',
                'partner_name' => 'Dr. Marine Scientist',
                'request_title' => 'Test Ocean Research Request',
                'offer_summary' => 'We can provide expertise in marine biology and oceanography.',
                'offer_url' => config('app.url').'/offers/12345',
                'requester_name' => 'John Researcher',
                'next_steps' => 'Please contact us to discuss further details.',
                'decline_reason' => 'The offer does not meet our current requirements.',
            ],
            default => [],
        };

        return array_merge($defaults, $additionalVariables);
    }

    /**
     * Parse variables from command line options
     *
     * @param  array<int, string>  $variables
     * @return array<string, string>
     */
    private function parseVariables(array $variables): array
    {
        $parsed = [];

        foreach ($variables as $variable) {
            if (str_contains($variable, ':')) {
                [$key, $value] = explode(':', $variable, 2);
                $parsed[$key] = $value;
            }
        }

        return $parsed;
    }
}
