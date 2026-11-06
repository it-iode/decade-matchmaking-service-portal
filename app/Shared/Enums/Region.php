<?php

namespace App\Shared\Enums;

enum Region: string
{
    case AFRICA = 'Africa';
    case ASIA_AND_THE_PACIFIC = 'Asia and the Pacific';
    case EUROPE = 'Europe';
    case LATIN_AMERICA_AND_THE_CARIBBEAN = 'Latin America and the Caribbean';
    case NORTH_AMERICA = 'North America';
    case MIDDLE_EAST = 'Middle East';
    case POLAR_REGIONS = 'Polar Regions (Arctic and Antarctic)';

    public function label(): string
    {
        return $this->value;
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
        $region = self::tryFrom($value);

        return $region?->label();
    }
}
