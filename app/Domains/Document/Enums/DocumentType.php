<?php

namespace App\Domains\Document\Enums;

enum DocumentType: string
{
    case FINANCIAL_BREAKDOWN_REPORT = 'financial_breakdown_report';
    case LESSON_LEARNED_REPORT = 'lesson_learned_report';
    case OFFER_DOCUMENT = 'offer_document';

    public function label(): string
    {
        return match ($this) {
            self::FINANCIAL_BREAKDOWN_REPORT => 'Financial Breakdown Report',
            self::LESSON_LEARNED_REPORT => 'Lesson Learned Report',
            self::OFFER_DOCUMENT => 'Offer Document',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn ($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
