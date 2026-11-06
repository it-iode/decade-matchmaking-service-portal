<?php

declare(strict_types=1);

namespace App\Domains\Request\Enums;

enum DecadeChallenge: string
{
    case MARINE_POLLUTION = 'challenge-1';
    case ECOSYSTEMS_BIODIVERSITY = 'challenge-2';
    case NOURISH_POPULATION = 'challenge-3';
    case SUSTAINABLE_OCEAN_ECONOMY = 'challenge-4';
    case CLIMATE_CHANGE_SOLUTIONS = 'challenge-5';
    case COMMUNITY_RESILIENCE = 'challenge-6';
    case OCEAN_OBSERVING_SYSTEM = 'challenge-7';
    case DIGITAL_OCEAN_REPRESENTATION = 'challenge-8';
    case SKILLS_KNOWLEDGE_PARTICIPATION = 'challenge-9';
    case SOCIETY_OCEAN_RELATIONSHIP = 'challenge-10';

    public function label(): string
    {
        return match ($this) {
            self::MARINE_POLLUTION => 'Understand and beat marine pollution',
            self::ECOSYSTEMS_BIODIVERSITY => 'Protect and restore ecosystems and biodiversity',
            self::NOURISH_POPULATION => 'Sustainably nourish the global population',
            self::SUSTAINABLE_OCEAN_ECONOMY => 'Develop a sustainable, resilient and equitable ocean economy',
            self::CLIMATE_CHANGE_SOLUTIONS => 'Unlock ocean-based solutions to climate change',
            self::COMMUNITY_RESILIENCE => 'Increase community resilience to ocean and coastal risks',
            self::OCEAN_OBSERVING_SYSTEM => 'Sustainably expand the Global Ocean Observing System',
            self::DIGITAL_OCEAN_REPRESENTATION => 'Create a digital representation of the ocean',
            self::SKILLS_KNOWLEDGE_PARTICIPATION => 'Skills, knowledge, technology and participation for all',
            self::SOCIETY_OCEAN_RELATIONSHIP => "Restore society's relationship with the ocean",
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function getOptions(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function getLabelByValue(string $value): ?string
    {
        return self::tryFrom($value)?->label();
    }
}
