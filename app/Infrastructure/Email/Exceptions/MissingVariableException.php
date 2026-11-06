<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

class MissingVariableException extends EmailTemplateException
{
    protected array $missingVariables = [];

    public static function forVariables(string $eventName, array $missingVariables): self
    {
        $exception = new self(
            sprintf(
                'Required variables missing for template "%s": %s',
                $eventName,
                implode(', ', $missingVariables)
            )
        );

        $exception->setEventName($eventName);
        $exception->missingVariables = $missingVariables;

        return $exception;
    }

    public function getMissingVariables(): array
    {
        return $this->missingVariables;
    }

    public function getUserMessage(): string
    {
        return 'Unable to send email due to missing information.';
    }
}
