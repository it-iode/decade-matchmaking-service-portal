<?php

namespace App\Shared\Enums;

enum YesNo: string
{
    case YES = 'yes';
    case NO = 'no';

    public function label(): string
    {
        return match ($this) {
            self::YES => 'Yes',
            self::NO => 'No',
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

    /**
     * Convert enum value to boolean
     */
    public function toBool(): bool
    {
        return $this === self::YES;
    }

    /**
     * Create enum from boolean value
     */
    public static function fromBool(bool $value): self
    {
        return $value ? self::YES : self::NO;
    }
}
