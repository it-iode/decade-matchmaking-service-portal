<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Contracts\AuthenticationStrategyInterface;
use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\Events\UserAuthenticated;
use App\Domains\Auth\Exceptions\AccountBlockedException;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Exceptions\OtpAuthenticationException;
use App\Domains\Auth\Exceptions\UnsupportedAuthenticationMethodException;
use App\Domains\Auth\Services\Strategies\OAuthAuthStrategy;
use App\Domains\Auth\Services\Strategies\OceanExpertAuthStrategy;
use App\Domains\Auth\Services\Strategies\OtpAuthStrategy;
use App\Domains\User\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthenticationService implements AuthenticationServiceInterface
{
    /** @var array<AuthenticationStrategyInterface> */
    private array $strategies;

    public function __construct(
        OceanExpertAuthStrategy $oceanExpertStrategy,
        OAuthAuthStrategy $oAuthStrategy,
        OtpAuthStrategy $otpAuthStrategy
    ) {
        $this->strategies = [
            $oceanExpertStrategy,
            $oAuthStrategy,
            $otpAuthStrategy,
        ];
    }

    /**
     * Authenticate user with email/password credentials
     *
     * @throws AccountBlockedException
     * @throws OceanExpertAuthenticationException
     * @throws UnsupportedAuthenticationMethodException
     */
    public function authenticateWithCredentials(string $email, string $password): User
    {
        $throttleKey = $this->getThrottleKey($email);

        $this->logAuthenticationAttempt($email, 'credentials');

        // Rate limiting check (Laravel RateLimiter facade)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->logAuthenticationFailure($email, 'credentials', 'rate_limited');

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        try {
            $result = $this->authenticate([
                'email' => $email,
                'password' => $password,
            ]);

            // Clear rate limit on success
            RateLimiter::clear($throttleKey);

            $this->completeAuthentication($result->user, $result);

            $this->logAuthenticationSuccess($result->user, $result->authMethod);

            return $result->user;
        } catch (OceanExpertAuthenticationException $e) {
            // Increment rate limit on failure
            RateLimiter::hit($throttleKey);

            $this->logAuthenticationFailure($email, 'credentials', $e->reason());

            throw $e;
        }
    }

    /**
     * Authenticate user with OAuth provider
     *
     * @throws \App\Domains\Auth\Exceptions\OAuthAuthenticationException
     * @throws ValidationException When rate limited
     */
    public function authenticateWithOAuth(SocialiteUser $socialUser, string $provider): User
    {
        $email = $socialUser->getEmail() ?? 'unknown';
        $throttleKey = $this->getThrottleKey($email);

        $this->logAuthenticationAttempt($email, $provider);

        // Rate limiting check (10 attempts per minute for OAuth)
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->logAuthenticationFailure($email, $provider, 'rate_limited');

            throw ValidationException::withMessages([
                'oauth' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        try {
            $result = $this->authenticate([
                'socialite_user' => $socialUser,
                'provider' => $provider,
            ]);

            // Clear rate limit on success
            RateLimiter::clear($throttleKey);

            $this->completeAuthentication($result->user, $result);

            $this->logAuthenticationSuccess($result->user, $result->authMethod);

            return $result->user;
        } catch (\Exception $e) {
            // Increment rate limit on failure
            RateLimiter::hit($throttleKey);

            $this->logAuthenticationFailure(
                $email,
                $provider,
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Authenticate user with OTP code
     *
     * @param  string  $email  User email address
     * @param  string  $code  OTP code to verify
     * @param  string|null  $ipAddress  Client IP address for logging
     * @return User Authenticated user instance
     *
     * @throws OtpAuthenticationException
     * @throws AccountBlockedException
     * @throws UnsupportedAuthenticationMethodException
     */
    public function authenticateWithOtp(string $email, string $code, ?string $ipAddress = null): User
    {
        $this->logAuthenticationAttempt($email, 'otp');

        try {
            $result = $this->authenticate([
                'email' => $email,
                'otp_code' => $code,
                'ip_address' => $ipAddress,
            ]);

            $this->completeAuthentication($result->user, $result);

            $this->logAuthenticationSuccess($result->user, $result->authMethod);

            return $result->user;
        } catch (\Exception $e) {
            $this->logAuthenticationFailure($email, 'otp', $e->getMessage());

            throw $e;
        }
    }

    /**
     * Complete the authentication process (login, session setup)
     *
     * @throws AccountBlockedException
     */
    public function completeAuthentication(User $user, AuthenticationResult $result): void
    {
        // Security check: Ensure user is not blocked
        $this->ensureUserIsActive($user);

        // Regenerate session to prevent fixation attacks (Laravel best practice)
        Session::regenerate();

        // Fire Laravel's built-in Login event
        Event::dispatch(new Login('web', $user, remember: true));

        // Login the user
        Auth::login($user, remember: true);

        // Store external token (Ocean Expert)
        if ($result->hasExternalToken()) {
            Session::put('external_api_token', $result->externalToken);
        }

        // Store OAuth metadata
        if ($result->hasOAuthMetadata()) {
            $oauth = $result->oauthMetadata;
            Session::put('oauth_provider', $oauth->provider);
            Session::put('oauth_id', $oauth->providerId);

            // Store OAuth tokens for potential refresh capability
            if ($oauth->accessToken) {
                Session::put('oauth_token', $oauth->accessToken);
            }
            if ($oauth->refreshToken) {
                Session::put('oauth_refresh_token', $oauth->refreshToken);
            }
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Fire custom authentication event
        event(new UserAuthenticated($user, $result->authMethod));
    }

    /**
     * Logout the authenticated user
     */
    public function logout(): void
    {
        $user = Auth::user();

        // Clear session data
        Session::forget([
            'external_api_token',
            'oauth_provider',
            'oauth_id',
            'oauth_token',
            'oauth_refresh_token',
        ]);

        // Logout
        Auth::logout();

        // Invalidate session and regenerate token (security best practice)
        Session::invalidate();
        Session::regenerateToken();

        if ($user) {
            Log::channel('auth')->info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);
        }
    }

    /**
     * Execute authentication using the appropriate strategy
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws UnsupportedAuthenticationMethodException
     */
    private function authenticate(array $credentials): AuthenticationResult
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($credentials)) {
                return $strategy->authenticate($credentials);
            }
        }

        throw new UnsupportedAuthenticationMethodException(
            'No authentication strategy available for the provided credentials'
        );
    }

    /**
     * Get rate limiting throttle key (email|ip)
     */
    private function getThrottleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.request()->ip());
    }

    /**
     * Ensure user account is active and not blocked
     *
     * @throws AccountBlockedException
     */
    private function ensureUserIsActive(User $user): void
    {
        if ($user->isBlocked()) {
            throw new AccountBlockedException(
                'Your account has been blocked. Please contact support.'
            );
        }
    }

    /**
     * Log authentication attempt
     */
    private function logAuthenticationAttempt(string $email, string $method): void
    {
        Log::channel('auth')->info("Authentication attempt with email: {$email} and method: {$method}", [
            'email' => $email,
            'method' => $method,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log successful authentication
     */
    private function logAuthenticationSuccess(User $user, string $method): void
    {
        Log::channel('auth')->info("Authentication successful with email: {$user->email} and method: {$method}", [
            'user_id' => $user->id,
            'email' => $user->email,
            'method' => $method,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Log failed authentication
     */
    private function logAuthenticationFailure(string $email, string $method, string $reason): void
    {
        Log::channel('auth')->warning('Authentication failed', [
            'email' => $email,
            'method' => $method,
            'reason' => $reason,
            'ip' => request()->ip(),
        ]);
    }
}
