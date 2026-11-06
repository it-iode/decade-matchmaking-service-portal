<?php

namespace App\Domains\Request\Enums;

enum TargetAudience: string
{
    case ACADEMIC = 'academic';
    case ALUMNI = 'alumni';
    case CIVIL_SOCIETY = 'civil-society';
    case SMALL_ISLAND_DEVELOPING_STATES = 'small-island-developing-states';
    case DECISION_MAKERS = 'decision-makers';
    case EARLY_CAREER_PROFESSIONALS = 'early-career-professionals';
    case RESEARCHERS = 'researchers';
    case DOCTORAL_OR_POSTDOCTORAL = 'doctoral-or-postdoctoral';
    case SCIENTISTS = 'scientists';
    case EXECUTIVES = 'executives';
    case TECHNICIANS = 'technicians';
    case WOMEN = 'women';
    case GOVERNMENT = 'government';
    case YOUTH = 'youth';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC => 'Academic',
            self::ALUMNI => 'Alumni',
            self::CIVIL_SOCIETY => 'Civil Society',
            self::SMALL_ISLAND_DEVELOPING_STATES => 'Small Island Developing States (SIDS)',
            self::DECISION_MAKERS => 'Decision Makers',
            self::EARLY_CAREER_PROFESSIONALS => 'Early Career Professionals',
            self::RESEARCHERS => 'Researchers',
            self::DOCTORAL_OR_POSTDOCTORAL => 'Doctoral or Postdoctoral',
            self::SCIENTISTS => 'Scientists',
            self::EXECUTIVES => 'Executives',
            self::TECHNICIANS => 'Technicians',
            self::WOMEN => 'Women',
            self::GOVERNMENT => 'Government',
            self::YOUTH => 'Youth',
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
        $audience = self::tryFrom($value);

        return $audience?->label();
    }
}
