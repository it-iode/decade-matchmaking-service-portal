<?php

declare(strict_types=1);

namespace App\Domains\Auth\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OceanExpertAuthenticationException extends Exception
{
    public const LEVEL_WARNING = 'warning';

    public const LEVEL_ERROR = 'error';

    /**
     * @param  string  $reason  Machine-readable failure reason (credentials_invalid, service_unavailable, ...)
     * @param  array<string, mixed>  $context  Diagnostic context safe to write to the auth log
     * @param  string  $logLevel  PSR-3 level the caller should log this failure at
     */
    public function __construct(
        string $message,
        private readonly string $reason,
        private readonly array $context = [],
        private readonly string $logLevel = self::LEVEL_WARNING,
    ) {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function logLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Whether this failure indicates a problem on our side or Ocean Expert's side,
     * as opposed to a user error such as a wrong password.
     */
    public function isOperationalError(): bool
    {
        return $this->logLevel === self::LEVEL_ERROR;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(Request $request): RedirectResponse
    {
        return redirect()
            ->route('sign.in')
            ->with('error', $this->getMessage())
            ->withInput($request->only('email'));
    }

    /**
     * Invalid credentials provided (4xx from Ocean Expert or an error payload)
     */
    public static function invalidCredentials(): self
    {
        return new self(
            'Invalid credentials provided',
            'credentials_invalid'
        );
    }

    /**
     * Ocean Expert service returned a 5xx response
     */
    public static function serviceUnavailable(?int $status = null, ?string $body = null): self
    {
        $context = array_filter([
            'status' => $status,
            'body' => $body === null ? null : Str::limit($body, 500),
        ], static fn ($value) => $value !== null);

        return new self(
            'Ocean Expert authentication service is currently unavailable',
            'service_unavailable',
            $context,
            self::LEVEL_ERROR
        );
    }

    /**
     * Ocean Expert responded successfully but with an unusable payload
     */
    public static function invalidResponse(string $detail): self
    {
        return new self(
            'Ocean Expert returned an unexpected response',
            'invalid_response',
            ['detail' => $detail],
            self::LEVEL_ERROR
        );
    }

    /**
     * Ocean Expert API error (unexpected failure while talking to Ocean Expert)
     */
    public static function apiError(string $message): self
    {
        return new self(
            "Ocean Expert API error: {$message}",
            'api_error',
            [],
            self::LEVEL_ERROR
        );
    }

    /**
     * User not found in Ocean Expert
     */
    public static function userNotFound(string $email): self
    {
        return new self(
            "User with email {$email} not found in Ocean Expert",
            'user_not_found'
        );
    }
}
