<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

class RateLimitExceededException extends EmailTemplateException
{
    protected string $eventName = '';

    protected array $context = [];

    public static function global(int $limit, int $retryAfter): self
    {
        $exception = new self("Global email rate limit exceeded. Limit: {$limit} emails per minute. Retry after {$retryAfter} seconds.");
        $exception->context = [
            'limit_type' => 'global',
            'limit' => $limit,
            'retry_after' => $retryAfter,
        ];

        return $exception;
    }

    public static function forUser(string $userEmail, int $limit, int $retryAfter): self
    {
        $exception = new self("User email rate limit exceeded for {$userEmail}. Limit: {$limit} emails per minute.");
        $exception->context = [
            'limit_type' => 'user',
            'user_email' => $userEmail,
            'limit' => $limit,
            'retry_after' => $retryAfter,
        ];

        return $exception;
    }

    public static function forEvent(string $eventName, int $limit, int $retryAfter): self
    {
        $exception = new self("Event email rate limit exceeded for '{$eventName}'. Limit: {$limit} emails per minute.");
        $exception->eventName = $eventName;
        $exception->context = [
            'limit_type' => 'event',
            'event_name' => $eventName,
            'limit' => $limit,
            'retry_after' => $retryAfter,
        ];

        return $exception;
    }

    public static function forUserAndEvent(string $userEmail, string $eventName, int $limit, int $retryAfter): self
    {
        $exception = new self("User-event email rate limit exceeded for {$userEmail} and event '{$eventName}'. Limit: {$limit} emails per hour.");
        $exception->eventName = $eventName;
        $exception->context = [
            'limit_type' => 'user_event',
            'user_email' => $userEmail,
            'event_name' => $eventName,
            'limit' => $limit,
            'retry_after' => $retryAfter,
        ];

        return $exception;
    }

    public function getUserMessage(): string
    {
        return 'Too many emails sent. Please try again later.';
    }

    public function getRetryAfter(): int
    {
        return $this->context['retry_after'] ?? 60;
    }
}
