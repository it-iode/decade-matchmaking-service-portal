<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Infrastructure\Email\Exceptions\MissingVariableException;
use App\Infrastructure\Email\Exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Validates email template variables against defined rules
 */
class VariableValidator
{
    /**
     * Validate variables against template rules
     *
     * @param  array<string, mixed>  $variables  The variables to validate
     * @param  array<string, string|array>  $rules  The validation rules (can be string or array)
     * @param  string  $eventName  The event name for error reporting
     * @return array<string, mixed> Validated and sanitized variables
     *
     * @throws MissingVariableException
     * @throws ValidationException
     */
    public function validate(array $variables, array $rules, string $eventName): array
    {
        // First check for missing required variables
        $missingVariables = $this->findMissingRequiredVariables($variables, $rules);
        if (! empty($missingVariables)) {
            throw MissingVariableException::forVariables($eventName, $missingVariables);
        }

        // Parse rules into Laravel validator format
        $validationRules = $this->parseRules($rules);

        // Validate using Laravel's validator
        $validator = Validator::make($variables, $validationRules);

        if ($validator->fails()) {
            throw ValidationException::withErrors($eventName, $validator->errors()->toArray());
        }

        // Return validated data with sanitization
        return $this->sanitizeVariables($validator->validated(), $rules);
    }

    /**
     * Find missing required variables
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, string>  $rules
     * @return array<int, string> List of missing variable names
     */
    private function findMissingRequiredVariables(array $variables, array $rules): array
    {
        $missing = [];

        foreach ($rules as $variable => $rule) {
            if ($this->isRequired($rule) && ! array_key_exists($variable, $variables)) {
                $missing[] = $variable;
            }
        }

        return $missing;
    }

    /**
     * Check if a rule indicates the field is required
     *
     * @param  string|array  $rule
     */
    private function isRequired($rule): bool
    {
        if (is_array($rule)) {
            return in_array('required', $rule, true);
        }

        return Str::contains($rule, 'required');
    }

    /**
     * Parse custom rule format into Laravel validator rules
     *
     * @param  array<string, string|array>  $rules
     * @return array<string, string|array>
     */
    private function parseRules(array $rules): array
    {
        $parsed = [];

        foreach ($rules as $variable => $rule) {
            if (is_array($rule)) {
                // Already in array format, just process each rule
                $parsed[$variable] = array_map(function ($r) {
                    return str_replace('optional', 'nullable', $r);
                }, $rule);
            } else {
                // Replace 'optional' with 'nullable' for Laravel validator
                $laravelRule = str_replace('optional', 'nullable', $rule);

                // Ensure proper rule formatting
                $laravelRule = $this->normalizeRule($laravelRule);

                $parsed[$variable] = $laravelRule;
            }
        }

        return $parsed;
    }

    /**
     * Normalize rule string for Laravel validator
     */
    private function normalizeRule(string $rule): string
    {
        // Split by pipe if not already
        $parts = explode('|', $rule);
        $normalized = [];

        foreach ($parts as $part) {
            $part = trim($part);

            // Handle common type conversions
            switch ($part) {
                case 'integer':
                case 'int':
                    $normalized[] = 'integer';
                    break;

                case 'bool':
                case 'boolean':
                    $normalized[] = 'boolean';
                    break;

                case 'date':
                    $normalized[] = 'date';
                    break;

                case 'datetime':
                    $normalized[] = 'date_format:Y-m-d H:i:s';
                    break;

                case 'url':
                    $normalized[] = 'url';
                    break;

                case 'email':
                    $normalized[] = 'email:rfc';
                    break;

                case 'array':
                    $normalized[] = 'array';
                    break;

                case 'html':
                    // Special type for HTML content that shouldn't be escaped
                    $normalized[] = 'string';
                    break;

                default:
                    // Keep as is if not a special case
                    $normalized[] = $part;
                    break;
            }
        }

        return implode('|', $normalized);
    }

    /**
     * Sanitize validated variables
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, string|array>  $rules
     * @return array<string, mixed>
     */
    private function sanitizeVariables(array $variables, array $rules): array
    {
        $sanitized = [];

        foreach ($variables as $key => $value) {
            // Check if this field should allow HTML
            $allowHtml = $this->shouldAllowHtml($key, $rules[$key] ?? '');
            $sanitized[$key] = $this->sanitizeValue($value, $allowHtml);
        }

        return $sanitized;
    }

    /**
     * Check if a field should allow HTML content
     *
     * @param  string|array  $rule
     */
    private function shouldAllowHtml(string $field, $rule): bool
    {
        // Check for 'html' rule type
        if (is_array($rule)) {
            return in_array('html', $rule, true);
        }

        return Str::contains($rule, 'html');
    }

    /**
     * Sanitize a single value
     *
     * @param  mixed  $value
     * @return mixed
     */
    private function sanitizeValue($value, bool $allowHtml = false)
    {
        if (is_string($value)) {
            // Trim whitespace
            $value = trim($value);

            if ($allowHtml) {
                // For HTML content, only strip dangerous tags but keep safe ones
                $value = $this->sanitizeHtml($value);
            } else {
                // For plain text, remove all tags and escape HTML entities
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
            }
        } elseif (is_array($value)) {
            // Recursively sanitize arrays
            $value = array_map(function ($item) use ($allowHtml) {
                return $this->sanitizeValue($item, $allowHtml);
            }, $value);
        }

        return $value;
    }

    /**
     * Sanitize HTML content to allow safe tags only
     */
    private function sanitizeHtml(string $html): string
    {
        // Allow only safe HTML tags for email content
        $allowedTags = '<p><br><strong><b><em><i><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><div><span><table><tr><td><th><tbody><thead><img>';

        // Strip dangerous tags but keep allowed ones
        $html = strip_tags($html, $allowedTags);

        // Remove dangerous attributes (onclick, onload, etc.)
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);

        // Remove javascript: protocol from links
        $html = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\']*["\']?/i', 'href="#"', $html);

        // Remove data: URIs except for images
        $html = preg_replace('/src\s*=\s*["\']?\s*data:(?!image\/)[^"\']*["\']?/i', 'src=""', $html);

        return $html;
    }

    /**
     * Merge default values with provided variables
     *
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function mergeWithDefaults(array $variables, array $defaults): array
    {
        return array_merge($defaults, array_filter($variables, fn ($v) => $v !== null));
    }
}
