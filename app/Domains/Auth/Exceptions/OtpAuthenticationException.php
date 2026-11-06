<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpAuthenticationException extends Exception
{
    public function __construct(
        string $message,
        private readonly ?string $context = null,
        private readonly ?int $retryAfter = null,
        private readonly ?int $remainingAttempts = null
    ) {
        parent::__construct($message);
    }

    /**
     * Report the exception with context
     */
    public function report(): void
    {
        Log::channel('auth')->warning('OTP authentication failed', [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $data = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->context,
        ];

        if ($this->retryAfter !== null) {
            $data['retry_after'] = $this->retryAfter;
        }

        if ($this->remainingAttempts !== null) {
            $data['remaining_attempts'] = $this->remainingAttempts;
        }

        $statusCode = match ($this->context) {
            'rate_limited' => 429,
            'user_blocked' => 403,
            'expired', 'max_attempts' => 400,
            'not_found' => 404,
            default => 422,
        };

        return response()->json($data, $statusCode);
    }

    /**
     * OTP code has expired
     */
    public static function expired(): self
    {
        return new self(
            'OTP has expired. Please request a new code.',
            'expired'
        );
    }

    /**
     * Invalid OTP code provided
     */
    public static function invalidCode(int $remainingAttempts): self
    {
        return new self(
            'Invalid OTP code.',
            'invalid_code',
            null,
            $remainingAttempts
        );
    }

    /**
     * Maximum verification attempts exceeded
     */
    public static function maxAttemptsExceeded(): self
    {
        return new self(
            'Maximum verification attempts exceeded. Please request a new code.',
            'max_attempts',
            null,
            0
        );
    }

    /**
     * Rate limited - too many OTP requests
     */
    public static function rateLimited(int $retryAfterSeconds): self
    {
        return new self(
            'Too many OTP requests. Please try again later.',
            'rate_limited',
            $retryAfterSeconds
        );
    }

    /**
     * No OTP request found
     */
    public static function notFound(): self
    {
        return new self(
            'No OTP request found. Please request a new code.',
            'not_found'
        );
    }

    /**
     * User account is blocked
     */
    public static function userBlocked(): self
    {
        return new self(
            'This account has been blocked. Please contact support.',
            'user_blocked'
        );
    }

    /**
     * User not found (generic message for security)
     */
    public static function userNotFound(): self
    {
        return new self(
            'If an account exists with this email, an OTP has been sent.',
            'user_not_found'
        );
    }
}
