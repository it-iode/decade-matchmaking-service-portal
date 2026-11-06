<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\DTOs\AuthenticationResult;

interface AuthenticationStrategyInterface
{
    /**
     * Authenticate and return the result.
     *
     * @param  array<string, mixed>  $credentials  Authentication credentials
     * @return AuthenticationResult The authenticated user with metadata
     *
     * @throws \App\Domains\Auth\Exceptions\OceanExpertAuthenticationException
     * @throws \App\Domains\Auth\Exceptions\OAuthAuthenticationException
     * @throws \App\Domains\Auth\Exceptions\OtpAuthenticationException
     */
    public function authenticate(array $credentials): AuthenticationResult;

    /**
     * Determine if this strategy supports the given credentials.
     *
     * @param  array<string, mixed>  $credentials  Credentials to check
     * @return bool True if strategy supports these credentials
     */
    public function supports(array $credentials): bool;
}
