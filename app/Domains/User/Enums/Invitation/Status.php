<?php

declare(strict_types=1);

namespace App\Domains\User\Enums\Invitation;

enum Status: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Get badge color for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'amber',
            self::ACCEPTED => 'green',
            self::EXPIRED => 'zinc',
        };
    }

    /**
     * Get all status options as array for dropdowns
     *
     * @return array<array{value: string, label: string}>
     */
    public static function getOptions(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
