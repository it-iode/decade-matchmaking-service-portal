<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Infrastructure\Email\Exceptions\MandrillApiException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use MailchimpTransactional\ApiClient;
use Throwable;

/**
 * Wrapper for Mandrill/Mailchimp Transactional API
 */
class MandrillClient
{
    private ApiClient $client;

    private array $defaultOptions;

    /**
     * @throws MandrillApiException
     */
    public function __construct(?ApiClient $client = null)
    {
        $this->client = $client ?? $this->createClient();
        $this->defaultOptions = $this->loadDefaultOptions();
    }

    /**
     * Send an email using a Mandrill template
     *
     * @param  array<string, mixed>  $variables  Template variables
     * @param  array<string, mixed>  $options  Additional options (cc, bcc, attachments, etc.)
     * @return array{id: string, status: string, reject_reason?: string}
     *
     * @throws MandrillApiException
     */
    public function sendTemplate(
        string $templateName,
        string $recipientEmail,
        string $recipientName,
        array $variables,
        array $options = []
    ): array {
        try {
            $message = $this->buildMessage($recipientEmail, $recipientName, $variables, $options);

            $response = $this->client->messages->sendTemplate([
                'template_name' => $templateName,
                'template_content' => $options['template_content'] ?? [], // Support for mc:edit regions
                'message' => $message,
                'async' => $options['async'] ?? false,
                'ip_pool' => $options['ip_pool'] ?? null,
                'send_at' => $options['send_at'] ?? null,
            ]);

            if ($response instanceof RequestException) {
                throw MandrillApiException::fromApiError($response->getMessage());
            }
            // Mandrill returns an array with one element per recipient
            if (! is_array($response) || empty($response)) {
                throw MandrillApiException::fromApiError('Empty response from Mandrill API');
            }

            $result = $response[0];

            // Convert stdClass to array if needed (Mailchimp library returns stdClass from json_decode)
            if ($result instanceof \stdClass) {
                $result = (array) $result;
            }

            // Check for rejection
            if ($result['status'] === 'rejected' || $result['status'] === 'invalid') {
                throw MandrillApiException::fromApiError(
                    sprintf('Email rejected: %s', $result['reject_reason'] ?? 'Unknown reason'),
                    $result['status'],
                    $result
                );
            }

            return [
                'id' => $result['_id'] ?? null,
                'status' => $result['status'],
                'reject_reason' => $result['reject_reason'] ?? null,
            ];
        } catch (MandrillApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw MandrillApiException::fromApiError(
                $e->getMessage(),
                null,
                [],
                $e
            );
        }
    }

    /**
     * Get information about a Mandrill template
     *
     * @return array{name: string, publish_name?: string, code?: string, subject?: string}
     *
     * @throws MandrillApiException
     */
    public function getTemplate(string $templateName): array
    {
        try {
            return $this->client->templates->info(['name' => $templateName]);
        } catch (Throwable $e) {
            throw MandrillApiException::fromApiError(
                sprintf('Failed to fetch template "%s": %s', $templateName, $e->getMessage()),
                null,
                [],
                $e
            );
        }
    }

    /**
     * List all available templates
     *
     * @return array<int, array{name: string, publish_name?: string}>
     *
     * @throws MandrillApiException
     */
    public function listTemplates(?string $label = null): array
    {
        try {
            return $this->client->templates->list(['label' => $label]);
        } catch (Throwable $e) {
            throw MandrillApiException::fromApiError(
                sprintf('Failed to list templates: %s', $e->getMessage()),
                null,
                [],
                $e
            );
        }
    }

    /**
     * Build the message array for Mandrill
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function buildMessage(
        string $recipientEmail,
        string $recipientName,
        array $variables,
        array $options
    ): array {
        $message = array_merge($this->defaultOptions, [
            'to' => [[
                'email' => $recipientEmail,
                'name' => $recipientName,
                'type' => 'to',
            ]],
            'merge_vars' => [[
                'rcpt' => $recipientEmail,
                'vars' => $this->formatVariables($variables),
            ]],
            'global_merge_vars' => $this->formatVariables($variables),
            'tags' => $options['tags'] ?? [],
            'metadata' => $options['metadata'] ?? [],
        ]);

        // Add CC recipients if provided
        if (! empty($options['cc'])) {
            foreach ((array) $options['cc'] as $cc) {
                $message['to'][] = [
                    'email' => is_array($cc) ? $cc['email'] : $cc,
                    'name' => is_array($cc) ? ($cc['name'] ?? '') : '',
                    'type' => 'cc',
                ];
            }
        }

        // Add BCC recipients if provided
        if (! empty($options['bcc'])) {
            foreach ((array) $options['bcc'] as $bcc) {
                $message['to'][] = [
                    'email' => is_array($bcc) ? $bcc['email'] : $bcc,
                    'name' => is_array($bcc) ? ($bcc['name'] ?? '') : '',
                    'type' => 'bcc',
                ];
            }
        }

        // Add attachments if provided
        if (! empty($options['attachments'])) {
            $message['attachments'] = $this->formatAttachments($options['attachments']);
        }

        // Override subject if provided
        if (! empty($options['subject'])) {
            $message['subject'] = $options['subject'];
        }

        return $message;
    }

    /**
     * Format variables for Mandrill merge_vars format
     *
     * @param  array<string, mixed>  $variables
     * @return array<int, array{name: string, content: mixed}>
     */
    private function formatVariables(array $variables): array
    {
        $formatted = [];

        foreach ($variables as $name => $content) {
            $formatted[] = [
                'name' => $name,
                'content' => $content,
            ];
        }

        return $formatted;
    }

    /**
     * Format attachments for Mandrill
     *
     * @param  array<int, array{type?: string, name?: string, content?: string}>  $attachments
     * @return array<int, array{type: string, name: string, content: string}>
     */
    private function formatAttachments(array $attachments): array
    {
        $formatted = [];

        foreach ($attachments as $attachment) {
            if (empty($attachment['content'])) {
                continue;
            }

            $formatted[] = [
                'type' => $attachment['type'] ?? 'application/octet-stream',
                'name' => $attachment['name'] ?? 'attachment',
                'content' => base64_encode($attachment['content']),
            ];
        }

        return $formatted;
    }

    /**
     * Create Mandrill API client instance
     */
    private function createClient(): ApiClient
    {
        $apiKey = Config::get('mail-templates.mandrill.api_key');

        if (empty($apiKey)) {
            throw MandrillApiException::fromApiError('Mandrill API key not configured');
        }

        $client = new ApiClient;
        $client->setApiKey($apiKey);

        return $client;
    }

    /**
     * Load default message options from config
     *
     * @return array<string, mixed>
     */
    private function loadDefaultOptions(): array
    {
        return [
            'from_email' => Config::get('mail-templates.mandrill.from_address', 'noreply@oceandecade.org'),
            'from_name' => Config::get('mail-templates.mandrill.from_name', 'Ocean Decade Portal'),
            'headers' => [
                'Reply-To' => Config::get('mail-templates.mandrill.reply_to', 'support@oceandecade.org'),
            ],
            'important' => false,
            'track_opens' => true,
            'track_clicks' => true,
            'auto_text' => true,
            'auto_html' => false,
            'inline_css' => true,
            'url_strip_qs' => false,
            'preserve_recipients' => false,
            'view_content_link' => false,
            'merge' => true,
            'merge_language' => 'mailchimp',
        ];
    }
}
