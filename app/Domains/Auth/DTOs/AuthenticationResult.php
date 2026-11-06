<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Domains\User\Models\User;

/**
 * Data Transfer Object for authentication results.
 *
 * Encapsulates the result of a successful authentication attempt,
 * providing type-safe access to the authenticated user and any
 * authentication method-specific metadata.
 */
final readonly class AuthenticationResult
{
    public function __construct(
        public User $user,
        public string $authMethod,
        public ?string $externalToken = null,
        public ?OAuthMetadata $oauthMetadata = null,
    ) {}

    /**
     * Check if this result includes an external API token.
     */
    public function hasExternalToken(): bool
    {
        return $this->externalToken !== null;
    }

    /**
     * Check if this result includes OAuth metadata.
     */
    public function hasOAuthMetadata(): bool
    {
        return $this->oauthMetadata !== null;
    }
}
