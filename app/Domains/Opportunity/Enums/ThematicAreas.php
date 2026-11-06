<?php

namespace App\Domains\Opportunity\Enums;

enum ThematicAreas: string
{
    case CODESIGN = 'co-design';
    case DATAQUALITY_DIGITALIZATION_SHARING = 'dataquality-digitalization-sharing';
    case DEEPSEA_SYSTEMS = 'deepsea-systems';
    case ECOSYSTEM_PROTECTION_RESTORATION = 'ecosystem-protection-restoration';
    case HAZARDS_RESILIENCE_EARLY_WARNING = 'hazards-resilience-early-warning';
    case MARINE_BIODIVERSITY = 'marine-biodiversity';
    case MARINE_POLLUTION = 'marine-pollution';
    case OCEAN_LITERACY_PUBLIC_ENGAGEMENT = 'ocean-literacy-public-engagement';
    case OCEAN_OBSERVING = 'ocean-observing';
    case OCEAN_CLIMATE_SYSTEMS = 'ocean-climate-systems';
    case POLAR_SYSTEMS = 'polar-systems';
    case SCIENCE_POLICY_INTERFACE = 'science-policy-interface';
    case SUSTAINABLE_BLUE_ECONOMY = 'sustainable-blue-economy';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CODESIGN => 'Co-Design',
            self::DATAQUALITY_DIGITALIZATION_SHARING => 'Data Quality, Digitalization and Sharing',
            self::DEEPSEA_SYSTEMS => 'Deep-Sea Systems',
            self::ECOSYSTEM_PROTECTION_RESTORATION => 'Ecosystem Protection and Restoration',
            self::HAZARDS_RESILIENCE_EARLY_WARNING => 'Hazards, Resilience and Early Warning',
            self::MARINE_BIODIVERSITY => 'Marine Biodiversity',
            self::MARINE_POLLUTION => 'Marine Pollution',
            self::OCEAN_LITERACY_PUBLIC_ENGAGEMENT => 'Ocean Literacy and Public Engagement',
            self::OCEAN_OBSERVING => 'Ocean Observing',
            self::OCEAN_CLIMATE_SYSTEMS => 'Ocean–Climate Systems',
            self::POLAR_SYSTEMS => 'Polar Systems',
            self::SCIENCE_POLICY_INTERFACE => 'Science–Policy Interface',
            self::SUSTAINABLE_BLUE_ECONOMY => 'Sustainable Blue Economy',
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
        $thematicArea = self::tryFrom($value);

        return $thematicArea?->label();
    }
}
