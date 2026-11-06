<?php

declare(strict_types=1);

namespace App\Domains\Auth\Events;

use App\Domains\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAuthenticated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     *
     * @param  User  $user  The authenticated user
     * @param  string  $method  Authentication method (ocean_expert, google, linkedin, otp)
     */
    public function __construct(
        public readonly User $user,
        public readonly string $method
    ) {}

    /**
     * Get authentication method display name
     */
    public function getMethodName(): string
    {
        return match ($this->method) {
            'credentials', 'ocean_expert' => 'Ocean Expert Credentials',
            'google' => 'Google OAuth',
            'linkedin' => 'LinkedIn OAuth',
            default => ucfirst($this->method),
        };
    }

    /**
     * Check if authentication was via OAuth
     */
    public function isOAuthAuthentication(): bool
    {
        return in_array($this->method, ['google', 'linkedin'], true);
    }

    /**
     * Check if authentication was via credentials
     */
    public function isCredentialsAuthentication(): bool
    {
        return in_array($this->method, ['credentials', 'ocean_expert'], true);
    }
}
