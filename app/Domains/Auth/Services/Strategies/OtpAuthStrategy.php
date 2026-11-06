<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Strategies;

use App\Domains\Auth\Contracts\AuthenticationStrategyInterface;
use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\Exceptions\OtpAuthenticationException;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;

class OtpAuthStrategy implements AuthenticationStrategyInterface
{
    /**
     * Authenticate user with OTP credentials
     *
     * @param  array{email: string, otp_code: string, ip_address?: string}  $credentials
     *
     * @throws OtpAuthenticationException
     */
    public function authenticate(array $credentials): AuthenticationResult
    {
        if (! isset($credentials['email'], $credentials['otp_code'])) {
            throw OtpAuthenticationException::notFound();
        }

        $email = strtolower(trim($credentials['email']));
        $otpCode = $credentials['otp_code'];
        $ipAddress = $credentials['ip_address'] ?? null;

        // Find user
        $user = User::where('email', $email)->first();

        if (! $user) {
            Log::channel('auth')->info('OTP authentication failed: user not found', [
                'email_hash' => hash('sha256', $email),
                'ip_address' => $ipAddress,
            ]);
            throw OtpAuthenticationException::notFound();
        }

        // Verify OTP using Spatie's native method
        $result = $user->consumeOneTimePassword($otpCode);

        if (! $result->isOk()) {
            Log::channel('auth')->info('OTP authentication failed', [
                'user_id' => $user->id,
                'result' => $result->value,
                'ip_address' => $ipAddress,
            ]);
            throw $this->mapResultToException($result);
        }

        Log::channel('auth')->info('OTP authentication successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ipAddress,
        ]);

        return new AuthenticationResult(
            user: $user,
            authMethod: 'otp',
        );
    }

    /**
     * Map ConsumeOneTimePasswordResult to appropriate OtpAuthenticationException
     */
    private function mapResultToException(ConsumeOneTimePasswordResult $result): OtpAuthenticationException
    {
        return match ($result) {
            ConsumeOneTimePasswordResult::NoOneTimePasswordsFound => OtpAuthenticationException::notFound(),
            ConsumeOneTimePasswordResult::IncorrectOneTimePassword => OtpAuthenticationException::invalidCode(0),
            ConsumeOneTimePasswordResult::OneTimePasswordExpired => OtpAuthenticationException::expired(),
            ConsumeOneTimePasswordResult::RateLimitExceeded => OtpAuthenticationException::maxAttemptsExceeded(),
            ConsumeOneTimePasswordResult::DifferentOrigin => new OtpAuthenticationException(
                'Security check failed. Please request a new code.',
                'origin_mismatch'
            ),
            default => new OtpAuthenticationException(
                'Authentication failed.',
                'unknown_error'
            ),
        };
    }

    /**
     * Check if this strategy supports the given credentials
     *
     * @param  array<string, mixed>  $credentials  Credentials to check
     */
    public function supports(array $credentials): bool
    {
        return isset($credentials['email'], $credentials['otp_code'])
            && ! isset($credentials['password'])
            && ! isset($credentials['socialite_user']);
    }
}
