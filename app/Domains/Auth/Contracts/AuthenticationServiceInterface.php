<?php

declare(strict_types=1);

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\Exceptions\OAuthAuthenticationException;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Exceptions\OtpAuthenticationException;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

interface AuthenticationServiceInterface
{
    /**
     * Authenticate user with email/password credentials
     *
     * @param  string  $email  User email address
     * @param  string  $password  User password
     * @return User Authenticated user instance
     *
     * @throws OceanExpertAuthenticationException
     * @throws ValidationException When rate limited
     */
    public function authenticateWithCredentials(string $email, string $password): User;

    /**
     * Authenticate user with OAuth provider
     *
     * @param  SocialiteUser  $socialUser  Socialite user instance from provider
     * @param  string  $provider  OAuth provider name (google, linkedin)
     * @return User Authenticated user instance
     *
     * @throws OAuthAuthenticationException
     */
    public function authenticateWithOAuth(SocialiteUser $socialUser, string $provider): User;

    /**
     * Complete the authentication process (login, session setup)
     *
     * @param  User  $user  User to authenticate
     * @param  AuthenticationResult  $result  Authentication result with metadata
     */
    public function completeAuthentication(User $user, AuthenticationResult $result): void;

    /**
     * Authenticate user with OTP code
     *
     * @param  string  $email  User email address
     * @param  string  $code  OTP code to verify
     * @param  string|null  $ipAddress  Client IP address for logging
     * @return User Authenticated user instance
     *
     * @throws OtpAuthenticationException
     */
    public function authenticateWithOtp(string $email, string $code, ?string $ipAddress = null): User;

    /**
     * Logout the authenticated user
     */
    public function logout(): void;
}
