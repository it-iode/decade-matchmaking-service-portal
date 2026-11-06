<?php

namespace App\Domains\Request\Enums;

enum DeliveryFormat: string
{
    case ONLINE = 'online';
    case ON_SITE = 'on-site';
    case BLENDED = 'blended';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::ON_SITE => 'On-site',
            self::BLENDED => 'Blended',
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
        $deliveryFormat = self::tryFrom($value);

        return $deliveryFormat?->label();
    }
}
