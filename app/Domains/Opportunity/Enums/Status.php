<?php

namespace App\Domains\Opportunity\Enums;

enum Status: int
{
    case ACTIVE = 1;
    case CLOSED = 2;
    case REJECTED = 3;
    case PENDING_REVIEW = 4;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::CLOSED => 'Closed',
            self::REJECTED => 'Rejected',
            self::PENDING_REVIEW => 'Pending Review',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function getLabelByValue($value): ?string
    {
        $status = self::tryFrom((int) $value);

        return $status?->label();
    }
}
