<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

class TemplateNotFoundException extends EmailTemplateException
{
    public static function forEvent(string $eventName): self
    {
        $exception = new self(
            sprintf('Email template configuration not found for event: %s', $eventName)
        );

        return $exception->setEventName($eventName);
    }

    public function getUserMessage(): string
    {
        return 'The email template for this action is not configured.';
    }
}
