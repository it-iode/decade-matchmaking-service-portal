<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

use Throwable;

class MandrillApiException extends EmailTemplateException
{
    protected ?string $mandrillCode = null;

    protected array $apiResponse = [];

    public static function fromApiError(string $message, ?string $code = null, array $response = [], ?Throwable $previous = null): self
    {
        $exception = new self(
            sprintf('Mandrill API error: %s', $message),
            0,
            $previous
        );

        $exception->mandrillCode = $code;
        $exception->apiResponse = $response;

        return $exception;
    }

    public function getMandrillCode(): ?string
    {
        return $this->mandrillCode;
    }

    public function getApiResponse(): array
    {
        return $this->apiResponse;
    }

    public function getUserMessage(): string
    {
        return 'Email service temporarily unavailable. Please try again later.';
    }

    /**
     * Determine if this is a recoverable error that should be retried
     */
    public function isRecoverable(): bool
    {
        // Rate limit and temporary errors are recoverable
        $recoverableCodes = ['rate_limit', 'service_unavailable', 'timeout'];

        return in_array($this->mandrillCode, $recoverableCodes, true);
    }
}
