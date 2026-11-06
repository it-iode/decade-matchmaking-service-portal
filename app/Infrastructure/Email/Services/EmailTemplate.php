<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use Illuminate\Support\Facades\Config;

/**
 * Value object representing an email template configuration
 */
final class EmailTemplate
{
    /**
     * @param  array<string, string>  $variables  Variable names with validation rules
     * @param  array<int, string>  $tags  Tags for Mandrill tracking
     * @param  array<string, mixed>  $metadata  Additional metadata
     */
    public function __construct(
        private readonly string $eventName,
        private readonly string $mandrillTemplateName,
        private readonly string $subject,
        private readonly array $variables,
        private readonly array $tags = [],
        private readonly array $metadata = []
    ) {}

    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Get the Mandrill template name with environment prefix
     */
    public function getMandrillName(): string
    {
        $environment = Config::get('app.env', 'production');
        $prefix = Config::get("mail-templates.environment_prefix.{$environment}", '');

        return $prefix.$this->mandrillTemplateName;
    }

    /**
     * Get the raw template name without environment prefix
     */
    public function getRawTemplateName(): string
    {
        return $this->mandrillTemplateName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get all variable definitions with their validation rules
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Get only the required variables
     */
    public function getRequiredVariables(): array
    {
        return array_filter(
            $this->variables,
            fn (string $rules) => str_contains($rules, 'required'),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Get only the optional variables
     */
    public function getOptionalVariables(): array
    {
        return array_filter(
            $this->variables,
            fn (string $rules) => str_contains($rules, 'optional'),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Check if a variable is required
     */
    public function isVariableRequired(string $variable): bool
    {
        return isset($this->variables[$variable]) &&
               str_contains($this->variables[$variable], 'required');
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Create from configuration array
     */
    public static function fromConfig(string $eventName, array $config): self
    {
        return new self(
            eventName: $eventName,
            mandrillTemplateName: $config['template_name'] ?? '',
            subject: $config['subject'] ?? '',
            variables: $config['variables'] ?? [],
            tags: $config['tags'] ?? [],
            metadata: $config['metadata'] ?? []
        );
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'template_name' => $this->mandrillTemplateName,
            'mandrill_name' => $this->getMandrillName(),
            'subject' => $this->subject,
            'variables' => $this->variables,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
        ];
    }
}
