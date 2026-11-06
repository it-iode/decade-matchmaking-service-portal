<?php

namespace App\Domains\Request\Enums;

enum RelatedActivity: string
{
    case TRAINING = 'training';
    case WORKSHOP = 'workshop';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::TRAINING => 'Training',
            self::WORKSHOP => 'Workshop',
            self::BOTH => 'Both',
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
        $relatedActivity = self::tryFrom($value);

        return $relatedActivity?->label();
    }
}
