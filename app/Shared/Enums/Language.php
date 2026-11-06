<?php

namespace App\Shared\Enums;

enum Language: string
{
    case ARABIC = 'arabic';
    case ENGLISH = 'english';
    case CHINESE = 'chinese';
    case RUSSIAN = 'russian';
    case FRENCH = 'french';
    case PORTUGUESE = 'portuguese';
    case SPANISH = 'spanish';
    case GERMAN = 'german';
    case ITALIAN = 'italian';
    case JAPANESE = 'japanese';
    case KOREAN = 'korean';
    case HINDI = 'hindi';
    case TURKISH = 'turkish';
    case DUTCH = 'dutch';
    case SWEDISH = 'swedish';
    case DANISH = 'danish';
    case NORWEGIAN = 'norwegian';
    case FINNISH = 'finnish';
    case POLISH = 'polish';
    case GREEK = 'greek';
    case HUNGARIAN = 'hungarian';
    case CZECH = 'czech';
    case ROMANIAN = 'romanian';
    case BULGARIAN = 'bulgarian';
    case SLOVAK = 'slovak';
    case CROATIAN = 'croatian';
    case SLOVENIAN = 'slovenian';
    case SERBIAN = 'serbian';
    case UKRAINIAN = 'ukrainian';
    case THAI = 'thai';
    case VIETNAMESE = 'vietnamese';
    case MALAY = 'malay';
    case INDONESIAN = 'indonesian';
    case FILIPINO = 'filipino';
    case SWAHILI = 'swahili';
    case PERSIAN = 'persian';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ARABIC => 'Arabic',
            self::ENGLISH => 'English',
            self::CHINESE => 'Chinese',
            self::RUSSIAN => 'Russian',
            self::FRENCH => 'French',
            self::PORTUGUESE => 'Portuguese',
            self::SPANISH => 'Spanish',
            self::GERMAN => 'German',
            self::ITALIAN => 'Italian',
            self::JAPANESE => 'Japanese',
            self::KOREAN => 'Korean',
            self::HINDI => 'Hindi',
            self::TURKISH => 'Turkish',
            self::DUTCH => 'Dutch',
            self::SWEDISH => 'Swedish',
            self::DANISH => 'Danish',
            self::NORWEGIAN => 'Norwegian',
            self::FINNISH => 'Finnish',
            self::POLISH => 'Polish',
            self::GREEK => 'Greek',
            self::HUNGARIAN => 'Hungarian',
            self::CZECH => 'Czech',
            self::ROMANIAN => 'Romanian',
            self::BULGARIAN => 'Bulgarian',
            self::SLOVAK => 'Slovak',
            self::CROATIAN => 'Croatian',
            self::SLOVENIAN => 'Slovenian',
            self::SERBIAN => 'Serbian',
            self::UKRAINIAN => 'Ukrainian',
            self::THAI => 'Thai',
            self::VIETNAMESE => 'Vietnamese',
            self::MALAY => 'Malay',
            self::INDONESIAN => 'Indonesian',
            self::FILIPINO => 'Filipino',
            self::SWAHILI => 'Swahili',
            self::PERSIAN => 'Persian',
            self::OTHER => 'Other'
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
        $language = self::tryFrom($value);

        return $language?->label();
    }
}
