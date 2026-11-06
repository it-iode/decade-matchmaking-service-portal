<?php

namespace App\Domains\Request\Enums;

enum ProjectStage: string
{
    case PLANNING = 'Planning';
    case APPROVED = 'Approved';
    case IMPLEMENTATION = 'Implementation';
    case CLOSED = 'Closed';
    case OTHER = 'Other';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planning',
            self::APPROVED => 'Approved',
            self::IMPLEMENTATION => 'In implementation',
            self::CLOSED => 'Closed',
            self::OTHER => 'Other',
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
        $case = self::tryFrom($value);

        return $case?->label();
    }
}
