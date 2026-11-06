<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

class ValidationException extends EmailTemplateException
{
    protected array $validationErrors = [];

    public static function withErrors(string $eventName, array $errors): self
    {
        $exception = new self(
            sprintf(
                'Variable validation failed for template "%s": %s',
                $eventName,
                json_encode($errors)
            )
        );

        $exception->setEventName($eventName);
        $exception->validationErrors = $errors;

        return $exception;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getUserMessage(): string
    {
        return 'Invalid data provided for email template.';
    }
}
