<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OAuthAuthenticationException extends Exception
{
    public function __construct(
        string $message,
        private readonly ?string $provider = null
    ) {
        parent::__construct($message);
    }

    /**
     * Report the exception with context
     */
    public function report(): void
    {
        Log::channel('auth')->warning('OAuth authentication failed', [
            'message' => $this->getMessage(),
            'provider' => $this->provider,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): RedirectResponse
    {
        return redirect()
            ->route('sign.in')
            ->with('error', $this->getMessage());
    }

    /**
     * Missing required credentials
     */
    public static function missingCredentials(): self
    {
        return new self(
            'OAuth user and provider are required'
        );
    }

    /**
     * Email not provided by OAuth provider
     */
    public static function missingEmail(string $provider): self
    {
        return new self(
            "Unable to retrieve email from {$provider}",
            $provider
        );
    }

    /**
     * OAuth provider error
     */
    public static function providerError(string $provider, string $message): self
    {
        return new self(
            "{$provider} OAuth error: {$message}",
            $provider
        );
    }

    /**
     * User creation failed
     */
    public static function userCreationFailed(string $provider): self
    {
        return new self(
            "Failed to create user from {$provider} OAuth data",
            $provider
        );
    }
}
