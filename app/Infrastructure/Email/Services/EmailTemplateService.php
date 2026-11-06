<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Exceptions\EmailTemplateException;
use App\Infrastructure\Email\Exceptions\MandrillApiException;
use App\Infrastructure\Email\Exceptions\MissingVariableException;
use App\Infrastructure\Email\Exceptions\TemplateNotFoundException;
use App\Infrastructure\Email\Exceptions\ValidationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Main orchestrator for sending templated emails
 */
readonly class EmailTemplateService
{
    public function __construct(
        private TemplateResolver $templateResolver,
        private VariableValidator $variableValidator,
        private MandrillClient $mandrillClient,
        private EmailLogger $emailLogger
    ) {}

    /**
     * Send a templated email
     *
     * @param  array<string, mixed>  $variables  Template variables
     * @param  array<string, mixed>  $options  Additional options (cc, bcc, attachments, etc.)
     *
     * @throws EmailTemplateException
     * @throws MissingVariableException
     * @throws TemplateNotFoundException
     * @throws ValidationException
     * @throws MandrillApiException
     * @throws Throwable
     */
    public function send(
        string $eventName,
        User $recipient,
        array $variables,
        array $options = []
    ): EmailResult {
        $logId = 0;

        try {
            // Resolve the template configuration
            $template = $this->templateResolver->resolve($eventName);

            // Add default variables
            $variables = $this->addDefaultVariables($variables, $recipient);

            // Validate variables
            $validatedVariables = $this->variableValidator->validate(
                $variables,
                $template->getVariables(),
                $eventName
            );

            // Log the email being queued/sent
            $logId = $this->emailLogger->logQueued(
                $eventName,
                $template->getMandrillName(),
                $recipient->email,
                $recipient,
                [
                    'tags' => $template->getTags(),
                    'options' => $options,
                ]
            );

            // Merge tags from template and options
            $options['tags'] = array_unique(array_merge(
                $template->getTags(),
                $options['tags'] ?? []
            ));

            // Add metadata
            $options['metadata'] = array_merge(
                [
                    'event' => $eventName,
                    'user_id' => $recipient->id,
                    'log_id' => $logId,
                ],
                $options['metadata'] ?? []
            );

            // Send via Mandrill
            $response = $this->mandrillClient->sendTemplate(
                $template->getMandrillName(),
                $recipient->email,
                $recipient->name ?? '',
                $validatedVariables,
                $options
            );

            // Log successful send
            $this->emailLogger->logSent($logId, $response['id'], $response);

            return new EmailResult(
                success: true,
                mandrillId: $response['id'],
                status: $response['status'],
                logId: $logId
            );
        } catch (MandrillApiException $e) {
            // Log rejection/failure
            if ($logId > 0) {
                $this->emailLogger->logRejected($logId, $e->getMessage(), [
                    'mandrill_code' => $e->getMandrillCode(),
                    'api_response' => $e->getApiResponse(),
                ]);
            } else {
                $this->emailLogger->logFailed(
                    $eventName,
                    $recipient->email,
                    $e->getMessage(),
                    ['exception' => get_class($e)]
                );
            }

            // Report to application logs
            $e->setEventName($eventName)->report();

            // Re-throw if not recoverable
            if (! $e->isRecoverable()) {
                throw $e;
            }

            return new EmailResult(
                success: false,
                error: $e->getMessage(),
                logId: $logId
            );
        } catch (EmailTemplateException $e) {
            // Log template-related failures
            $this->emailLogger->logFailed(
                $eventName,
                $recipient->email,
                $e->getMessage(),
                ['exception' => get_class($e)]
            );

            // Report and re-throw
            $e->setEventName($eventName)->report();
            throw $e;
        } catch (Throwable $e) {
            // Log unexpected errors
            $this->emailLogger->logFailed(
                $eventName,
                $recipient->email,
                $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            Log::error('Unexpected error in EmailTemplateService', [
                'event' => $eventName,
                'recipient' => $recipient->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send an email to multiple recipients
     *
     * @param  array<User>  $recipients
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $options
     * @return array<int, EmailResult>
     */
    public function sendToMultiple(
        string $eventName,
        array $recipients,
        array $variables,
        array $options = []
    ): array {
        $results = [];

        foreach ($recipients as $recipient) {
            try {
                $results[] = $this->send($eventName, $recipient, $variables, $options);
            } catch (Throwable $e) {
                // Log error but continue with other recipients
                Log::error('Failed to send email to recipient', [
                    'event' => $eventName,
                    'recipient' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);

                $results[] = new EmailResult(
                    success: false,
                    error: $e->getMessage()
                );
            }
        }

        return $results;
    }

    /**
     * Preview an email template with variables (for testing)
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws TemplateNotFoundException
     */
    public function preview(string $eventName, array $variables): array
    {
        $template = $this->templateResolver->resolve($eventName);

        // Create a dummy user for preview
        $dummyUser = new User;
        $dummyUser->email = $variables['email'] ?? 'preview@example.com';
        $dummyUser->name = $variables['name'] ?? 'Preview User';

        $variables = $this->addDefaultVariables($variables, $dummyUser);

        // Validate variables
        $validatedVariables = $this->variableValidator->validate(
            $variables,
            $template->getVariables(),
            $eventName
        );

        return [
            'event_name' => $eventName,
            'template_name' => $template->getMandrillName(),
            'subject' => $template->getSubject(),
            'variables' => $validatedVariables,
            'tags' => $template->getTags(),
            'required_variables' => array_keys($template->getRequiredVariables()),
            'optional_variables' => array_keys($template->getOptionalVariables()),
        ];
    }

    /**
     * Check if a template exists for an event
     */
    public function templateExists(string $eventName): bool
    {
        return $this->templateResolver->exists($eventName);
    }

    /**
     * Get all available templates
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableTemplates(): array
    {
        $templates = $this->templateResolver->getAllTemplates();

        return array_map(function ($template) {
            return $template->toArray();
        }, $templates);
    }

    /**
     * Validate that Mandrill has the template
     */
    public function validateMandrillTemplate(string $eventName): bool
    {
        try {
            $template = $this->templateResolver->resolve($eventName);
            $mandrillTemplate = $this->mandrillClient->getTemplate($template->getMandrillName());

            return ! empty($mandrillTemplate['name']);
        } catch (Throwable $e) {
            Log::warning('Failed to validate Mandrill template', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add default variables that are always available
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function addDefaultVariables(array $variables, User $recipient): array
    {
        $defaults = [
            'user_name' => $recipient->name ?? 'User',
            'user_email' => $recipient->email,
            'portal_url' => config('app.url'),
            'current_year' => date('Y'),
            'support_email' => config('mail-templates.mandrill.reply_to', 'support@oceandecade.org'),
        ];

        return array_merge($defaults, $variables);
    }
}
