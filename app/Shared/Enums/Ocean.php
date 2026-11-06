<?php

namespace App\Shared\Enums;

enum Ocean: string
{
    case ATLANTIC_OCEAN = 'atlantic-ocean';
    case PACIFIC_OCEAN = 'pacific-ocean';
    case INDIAN_OCEAN = 'indian-ocean';
    case ARCTIC_OCEAN = 'arctic-ocean';
    case SOUTHERN_OCEAN = 'southern-ocean';
    case MEDITERRANEAN_SEA = 'mediterranean-sea';
    case CASPIAN_SEA = 'caspian-sea';

    public function label(): string
    {
        return match ($this) {
            self::ATLANTIC_OCEAN => 'Atlantic Ocean',
            self::PACIFIC_OCEAN => 'Pacific Ocean',
            self::INDIAN_OCEAN => 'Indian Ocean',
            self::ARCTIC_OCEAN => 'Arctic Ocean',
            self::SOUTHERN_OCEAN => 'Southern Ocean',
            self::MEDITERRANEAN_SEA => 'Mediterranean Sea',
            self::CASPIAN_SEA => 'Caspian Sea',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function getLabelByValue(string $value): ?string
    {
        $ocean = self::tryFrom($value);

        return $ocean?->label();
    }
}
