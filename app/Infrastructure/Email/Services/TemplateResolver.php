<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Infrastructure\Email\Exceptions\TemplateNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Resolves email templates from configuration
 */
class TemplateResolver
{
    private const CACHE_PREFIX = 'email_template:';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Resolve a template by event name
     *
     * @throws TemplateNotFoundException
     */
    public function resolve(string $eventName): EmailTemplate
    {
        // Try to get from cache first (but handle cache failures gracefully)
        $cacheKey = self::CACHE_PREFIX.$eventName;

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($eventName) {
                $config = $this->getTemplateConfig($eventName);

                if ($config === null) {
                    throw TemplateNotFoundException::forEvent($eventName);
                }

                return EmailTemplate::fromConfig($eventName, $config);
            });
        } catch (TemplateNotFoundException $e) {
            // Re-throw template not found exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Cache failure, fallback to non-cached resolution
            $config = $this->getTemplateConfig($eventName);

            if ($config === null) {
                throw TemplateNotFoundException::forEvent($eventName);
            }

            return EmailTemplate::fromConfig($eventName, $config);
        }
    }

    /**
     * Check if a template exists for the given event
     */
    public function exists(string $eventName): bool
    {
        return $this->getTemplateConfig($eventName) !== null;
    }

    /**
     * Get all available templates
     *
     * @return array<string, EmailTemplate>
     */
    public function getAllTemplates(): array
    {
        $templates = [];
        $allConfigs = Config::get('mail-templates.templates', []);

        foreach ($allConfigs as $eventName => $config) {
            try {
                $templates[$eventName] = EmailTemplate::fromConfig($eventName, $config);
            } catch (\Throwable $e) {
                // Skip invalid configurations
                continue;
            }
        }

        return $templates;
    }

    /**
     * Get templates by tag
     *
     * @return array<string, EmailTemplate>
     */
    public function getTemplatesByTag(string $tag): array
    {
        $templates = [];
        $allConfigs = Config::get('mail-templates.templates', []);

        foreach ($allConfigs as $eventName => $config) {
            if (in_array($tag, $config['tags'] ?? [], true)) {
                try {
                    $templates[$eventName] = EmailTemplate::fromConfig($eventName, $config);
                } catch (\Throwable $e) {
                    // Skip invalid configurations
                    continue;
                }
            }
        }

        return $templates;
    }

    /**
     * Clear cached template
     */
    public function clearCache(string $eventName): void
    {
        Cache::forget(self::CACHE_PREFIX.$eventName);
    }

    /**
     * Clear all cached templates
     */
    public function clearAllCache(): void
    {
        $allConfigs = Config::get('mail-templates.templates', []);

        foreach (array_keys($allConfigs) as $eventName) {
            $this->clearCache($eventName);
        }
    }

    /**
     * Get template configuration from config file
     *
     * @return array<string, mixed>|null
     */
    private function getTemplateConfig(string $eventName): ?array
    {
        // Get all templates first to avoid dot notation issues with event names
        $templates = Config::get('mail-templates.templates', []);

        return $templates[$eventName] ?? null;
    }

    /**
     * Validate that a template configuration has all required fields
     */
    public function validateTemplateConfig(array $config): bool
    {
        $requiredFields = ['template_name', 'variables'];

        foreach ($requiredFields as $field) {
            if (! isset($config[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get templates grouped by category (based on event name prefix)
     *
     * @return array<string, array<string, EmailTemplate>>
     */
    public function getTemplatesGroupedByCategory(): array
    {
        $grouped = [];
        $templates = $this->getAllTemplates();

        foreach ($templates as $eventName => $template) {
            // Extract category from event name (e.g., 'user' from 'user.registered')
            $parts = explode('.', $eventName);
            $category = $parts[0] ?? 'other';

            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][$eventName] = $template;
        }

        return $grouped;
    }
}
