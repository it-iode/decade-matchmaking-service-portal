<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

/**
 * Result object for email operations
 */
readonly class EmailResult
{
    public function __construct(
        public bool $success,
        public ?string $mandrillId = null,
        public ?string $status = null,
        public ?string $error = null,
        public int $logId = 0
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'mandrill_id' => $this->mandrillId,
            'status' => $this->status,
            'error' => $this->error,
            'log_id' => $this->logId,
        ];
    }
}
