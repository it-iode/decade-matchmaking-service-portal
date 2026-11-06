<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * Data Transfer Object for OAuth authentication metadata.
 *
 * Contains provider-specific data from OAuth authentication,
 * including tokens for potential refresh operations.
 */
final readonly class OAuthMetadata
{
    public function __construct(
        public string $provider,
        public string $providerId,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
    ) {}
}
