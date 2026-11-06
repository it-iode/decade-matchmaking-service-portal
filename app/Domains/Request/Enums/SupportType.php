<?php

namespace App\Domains\Request\Enums;

enum SupportType: string
{
    case FUNDING_WORKSHOP = 'funding-to-organize-a-workshop-ou-formation';
    case TECHNICAL_SUPPORT = 'technical-support-for-planning-and-delivering-a-workshop-ou-training';
    case FACILITATION_SUPPORT = 'facilitation-or-coordination-support';
    case PARTICIPATION_EXISTING = 'participation-in-an-existing-training-or-capacity-building-event';
    case ACCESS_MATERIALS = 'access-to-training-materials-or-curriculum';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FUNDING_WORKSHOP => 'Funding to organize a workshop or training',
            self::TECHNICAL_SUPPORT => 'Technical support for planning and delivering a workshop or training',
            self::FACILITATION_SUPPORT => 'Facilitation or coordination support',
            self::PARTICIPATION_EXISTING => 'Support for participation of additional participants (e.g., SIDS, LDCs, ECOPs) in training/workshops organized by the requesting institution.',
            self::ACCESS_MATERIALS => 'Access to training materials or curriculum',
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
        $supportType = self::tryFrom($value);

        return $supportType?->label();
    }
}
