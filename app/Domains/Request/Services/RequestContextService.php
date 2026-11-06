<?php

declare(strict_types=1);

namespace App\Domains\Request\Services;

/**
 * Centralized service for request context configuration
 *
 * Provides consistent context-based configuration for both list and detail views
 * Following Single Responsibility Principle
 */
class RequestContextService
{
    /**
     * Available request contexts
     */
    public const CONTEXT_ADMIN = 'admin';

    public const CONTEXT_USER_OWN = 'user_own';

    public const CONTEXT_PUBLIC = 'public';

    public const CONTEXT_MATCHED = 'matched';

    public const CONTEXT_SUBSCRIBED = 'subscribed';

    /**
     * Get all valid contexts.
     *
     * @return array<string>
     */
    public static function getValidContexts(): array
    {
        return [
            self::CONTEXT_ADMIN,
            self::CONTEXT_USER_OWN,
            self::CONTEXT_PUBLIC,
            self::CONTEXT_MATCHED,
            self::CONTEXT_SUBSCRIBED,
        ];
    }

    /**
     * Validate a context string.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateContext(string $context): void
    {
        if (! in_array($context, self::getValidContexts(), true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid context "%s". Valid contexts are: %s',
                    $context,
                    implode(', ', self::getValidContexts())
                )
            );
        }
    }

    /**
     * Get context-specific banner configuration for detail view
     *
     * @param  string  $context  The context identifier
     * @param  string  $requestTitle  The request title for dynamic content
     * @return array{title: string, description: string, image: string}
     */
    public function getDetailBannerConfig(string $context, string $requestTitle): array
    {
        return match ($context) {
            self::CONTEXT_USER_OWN => [
                'title' => $requestTitle,
                'description' => 'View and manage details of your submitted request.',
                'image' => '/assets/img/sidebar.png',
            ],
            self::CONTEXT_MATCHED => [
                'title' => $requestTitle,
                'description' => 'Review details of this matched request and manage your partnership.',
                'image' => '/assets/img/sidebar.png',
            ],
            self::CONTEXT_SUBSCRIBED => [
                'title' => $requestTitle,
                'description' => 'View details of this request you are subscribed to for updates.',
                'image' => '/assets/img/sidebar.png',
            ],
            self::CONTEXT_PUBLIC => [
                'title' => $requestTitle,
                'description' => 'View this public training request and explore partnership opportunities.',
                'image' => '/assets/img/sidebar.png',
            ],
            default => [
                'title' => $requestTitle,
                'description' => 'View request details here.',
                'image' => '/assets/img/sidebar.png',
            ],
        };
    }
}
