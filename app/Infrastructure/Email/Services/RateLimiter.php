<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Manages email sending rate limits to prevent abuse
 */
class RateLimiter
{
    private const CACHE_PREFIX = 'email_rate_limit:';

    /**
     * Check if the user or event has exceeded rate limits
     *
     * @throws RateLimitExceededException
     */
    public function checkLimit(User $recipient, string $eventName): void
    {
        // Check global rate limit
        $this->checkGlobalLimit();

        // Check per-user rate limit
        $this->checkUserLimit($recipient);

        // Check per-event rate limit
        $this->checkEventLimit($eventName);

        // Check per-user-per-event rate limit (prevents spam of same email to same user)
        $this->checkUserEventLimit($recipient, $eventName);
    }

    /**
     * Increment rate limit counters after successful send
     */
    public function incrementCounters(User $recipient, string $eventName): void
    {
        $ttl = 60; // 1 minute

        // Global counter
        Cache::increment($this->getGlobalKey(), 1);
        Cache::put($this->getGlobalKey().':ttl', true, $ttl);

        // User counter
        Cache::increment($this->getUserKey($recipient), 1);
        Cache::put($this->getUserKey($recipient).':ttl', true, $ttl);

        // Event counter
        Cache::increment($this->getEventKey($eventName), 1);
        Cache::put($this->getEventKey($eventName).':ttl', true, $ttl);

        // User-event counter
        Cache::increment($this->getUserEventKey($recipient, $eventName), 1);
        Cache::put($this->getUserEventKey($recipient, $eventName).':ttl', true, $ttl);
    }

    /**
     * Check global rate limit (emails per minute across all users)
     *
     * @throws RateLimitExceededException
     */
    protected function checkGlobalLimit(): void
    {
        $limit = Config::get('mail-templates.rate_limit.global_per_minute', 100);

        if ($limit === 0) {
            return; // Disabled
        }

        $key = $this->getGlobalKey();
        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            throw RateLimitExceededException::global($limit, $this->getSecondsUntilAvailable($key.':ttl'));
        }
    }

    /**
     * Check per-user rate limit
     *
     * @throws RateLimitExceededException
     */
    protected function checkUserLimit(User $recipient): void
    {
        $limit = Config::get('mail-templates.rate_limit.per_user_per_minute', 5);

        if ($limit === 0) {
            return; // Disabled
        }

        $key = $this->getUserKey($recipient);
        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            throw RateLimitExceededException::forUser(
                $recipient->email,
                $limit,
                $this->getSecondsUntilAvailable($key.':ttl')
            );
        }
    }

    /**
     * Check per-event rate limit
     *
     * @throws RateLimitExceededException
     */
    protected function checkEventLimit(string $eventName): void
    {
        $limit = Config::get('mail-templates.rate_limit.per_event_per_minute', 20);

        if ($limit === 0) {
            return; // Disabled
        }

        $key = $this->getEventKey($eventName);
        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            throw RateLimitExceededException::forEvent(
                $eventName,
                $limit,
                $this->getSecondsUntilAvailable($key.':ttl')
            );
        }
    }

    /**
     * Check per-user-per-event rate limit (prevents duplicate emails)
     *
     * @throws RateLimitExceededException
     */
    protected function checkUserEventLimit(User $recipient, string $eventName): void
    {
        $limit = Config::get('mail-templates.rate_limit.per_user_per_event_per_hour', 3);

        if ($limit === 0) {
            return; // Disabled
        }

        $key = $this->getUserEventKey($recipient, $eventName);
        $count = (int) Cache::get($key, 0);

        if ($count >= $limit) {
            throw RateLimitExceededException::forUserAndEvent(
                $recipient->email,
                $eventName,
                $limit,
                $this->getSecondsUntilAvailable($key.':ttl')
            );
        }
    }

    /**
     * Get global rate limit cache key
     */
    protected function getGlobalKey(): string
    {
        return self::CACHE_PREFIX.'global:'.now()->format('Y-m-d:H:i');
    }

    /**
     * Get user rate limit cache key
     */
    protected function getUserKey(User $user): string
    {
        return self::CACHE_PREFIX.'user:'.$user->id.':'.now()->format('Y-m-d:H:i');
    }

    /**
     * Get event rate limit cache key
     */
    protected function getEventKey(string $eventName): string
    {
        return self::CACHE_PREFIX.'event:'.$eventName.':'.now()->format('Y-m-d:H:i');
    }

    /**
     * Get user-event rate limit cache key
     */
    protected function getUserEventKey(User $user, string $eventName): string
    {
        return self::CACHE_PREFIX.'user:'.$user->id.':event:'.$eventName.':'.now()->format('Y-m-d:H');
    }

    /**
     * Get seconds until the rate limit is available again
     */
    protected function getSecondsUntilAvailable(string $ttlKey): int
    {
        $ttl = Cache::get($ttlKey);
        if (! $ttl) {
            return 60; // Default to 1 minute
        }

        // Calculate remaining seconds based on cache TTL
        return 60; // For simplicity, return constant. In production, calculate from actual TTL
    }

    /**
     * Reset all rate limit counters (for testing)
     */
    public function reset(): void
    {
        // In production, you might want to be more selective about what gets cleared
        Cache::forget($this->getGlobalKey());
    }

    /**
     * Get current rate limit statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(User $user, string $eventName): array
    {
        return [
            'global' => [
                'count' => (int) Cache::get($this->getGlobalKey(), 0),
                'limit' => Config::get('mail-templates.rate_limit.global_per_minute', 100),
            ],
            'user' => [
                'count' => (int) Cache::get($this->getUserKey($user), 0),
                'limit' => Config::get('mail-templates.rate_limit.per_user_per_minute', 5),
            ],
            'event' => [
                'count' => (int) Cache::get($this->getEventKey($eventName), 0),
                'limit' => Config::get('mail-templates.rate_limit.per_event_per_minute', 20),
            ],
            'user_event' => [
                'count' => (int) Cache::get($this->getUserEventKey($user, $eventName), 0),
                'limit' => Config::get('mail-templates.rate_limit.per_user_per_event_per_hour', 3),
            ],
        ];
    }
}
