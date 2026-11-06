<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

abstract class EmailTemplateException extends Exception
{
    protected string $eventName = '';

    protected array $context = [];

    /**
     * Set the event name that triggered this exception
     */
    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Set additional context information
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Report the exception to logs
     */
    public function report(): void
    {
        Log::error($this->getMessage(), [
            'exception' => static::class,
            'event' => $this->eventName,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    /**
     * Get a user-friendly error message
     */
    public function getUserMessage(): string
    {
        return 'An error occurred while sending the email. Our team has been notified.';
    }
}
