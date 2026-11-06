<?php

namespace App\Domains\Request\Enums;

enum SubTheme: string
{
    case MAPPING_OCEAN_CLIMATE = 'mapping-and-modeling-ocean-climate-interactions';
    case MARINE_CO2_REMOVAL = 'marine-co2-removal';
    case OCEAN_ACIDIFICATION = 'ocean-acidification';
    case OCEANS_HUMAN_HEALTH = 'impact-of-oceans-on-human-health';
    case CUMULATIVE_IMPACTS = 'measuring-cumulative-impacts-and-multiple-stressors';
    case LOW_COST_TECHNOLOGY = 'low-cost-technology-and-infrastructure-solutions-for data gathering and management';
    case DATA_MANAGEMENT = 'data-management-fair-and-care-principles';
    case MAPPING_BIODIVERSITY = 'mapping-and-modelling-biodiversity';
    case ECOSYSTEM_APPROACH_FISHERIES = 'ecosystem-approach-to-fisheries';
    case BBNJ_AGREEMENT = 'implementing-the-bbnj-agreement';
    case EDNA_TECHNIQUES = 'edna-techniques';
    case SCIENCE_COMMUNICATION = 'science-communication-for-policy-development';
    case POLICY_INFLUENCE = 'working-with-and-influencing-policymakers';
    case SUSTAINABLE_OCEAN_PLANNING = 'sustainable-ocean-planning';
    case STAKEHOLDER_ENGAGEMENT = 'stakeholder-engagement-via-transdisciplinary-approaches';
    case LOCAL_INDIGENOUS_KNOWLEDGE = 'engaging-local-and-indigenous-knowledge-holders';
    case PROJECT_MANAGEMENT = 'managing-leading-and-financing-ocean-projects';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MAPPING_OCEAN_CLIMATE => 'Mapping & modeling ocean-climate interactions',
            self::MARINE_CO2_REMOVAL => 'Marine CO2 removal',
            self::OCEAN_ACIDIFICATION => 'Ocean acidification',
            self::OCEANS_HUMAN_HEALTH => 'Impact of oceans on human health',
            self::CUMULATIVE_IMPACTS => 'Measuring cumulative impacts and multiple stressors',
            self::LOW_COST_TECHNOLOGY => 'Low-cost technology & infrastructure solutions for data gathering and management',
            self::DATA_MANAGEMENT => 'Data management (FAIR and CARE principles)',
            self::MAPPING_BIODIVERSITY => 'Mapping and modelling biodiversity',
            self::ECOSYSTEM_APPROACH_FISHERIES => 'Ecosystem Approach to Fisheries',
            self::BBNJ_AGREEMENT => 'Implementing the BBNJ Agreement',
            self::EDNA_TECHNIQUES => 'eDNA techniques',
            self::SCIENCE_COMMUNICATION => 'Science communication for policy development',
            self::POLICY_INFLUENCE => 'Working with & influencing policymakers',
            self::SUSTAINABLE_OCEAN_PLANNING => 'Sustainable Ocean Planning',
            self::STAKEHOLDER_ENGAGEMENT => 'Stakeholder engagement via transdisciplinary approaches',
            self::LOCAL_INDIGENOUS_KNOWLEDGE => 'Engaging Local & Indigenous Knowledge holders',
            self::PROJECT_MANAGEMENT => 'Managing, leading, & financing ocean projects',
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
        $subTheme = self::tryFrom($value);

        return $subTheme?->label();
    }
}
