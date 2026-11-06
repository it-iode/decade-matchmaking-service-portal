<?php

declare(strict_types=1);

namespace App\Domains\Request\Exceptions;

use Exception;

/**
 * Base exception for all request-related errors.
 *
 * This exception serves as the parent class for all custom request exceptions,
 * providing a consistent interface and HTTP status code support.
 */
class RequestException extends Exception
{
    /**
     * Create a new request exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $statusCode  The HTTP status code
     * @param  \Throwable|null  $previous  The previous throwable used for exception chaining
     */
    public function __construct(
        string $message = '',
        protected int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
