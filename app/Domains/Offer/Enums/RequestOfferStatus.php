<?php

namespace App\Domains\Offer\Enums;

enum RequestOfferStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function getLabelByValue(int $value): ?string
    {
        $status = self::tryFrom($value);

        return $status?->label();
    }
}
