<?php

namespace App\Domains\Opportunity\Requests;

use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Shared\Enums\Language;
use App\Shared\Support\UrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users to create opportunities
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $excludeIfReduced = Rule::excludeIf(fn () => $this->isReducedForm());
        $requiredUnlessReduced = Rule::requiredIf(fn () => ! $this->isReducedForm());

        return [
            'co_organizers' => ['required', 'array'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(Type::class)],
            'closing_date' => ['required', 'date', Rule::date()->after('today')],
            'coverage_activity' => [$excludeIfReduced, $requiredUnlessReduced],
            'implementation_location' => [$excludeIfReduced, $requiredUnlessReduced],
            'thematic_areas' => [$excludeIfReduced, $requiredUnlessReduced, 'array'],
            'thematic_areas.*' => [Rule::enum(ThematicAreas::class)],
            'thematic_areas_other' => [
                Rule::excludeIf(fn () => ! in_array(ThematicAreas::OTHER->value, $this->input('thematic_areas', []))),
                'string',
            ],
            'target_audience' => ['required', 'array'],
            'target_audience.*' => [Rule::enum(TargetAudience::class)],
            'target_audience_other' => [
                Rule::excludeIf(fn () => ! in_array(TargetAudience::OTHER->value, $this->input('target_audience', []))),
                'string',
            ],
            'target_languages' => [$excludeIfReduced, $requiredUnlessReduced],
            'target_languages.*' => [Rule::enum(Language::class)],
            'target_languages_other' => [
                Rule::excludeIf(fn () => ! in_array(Language::OTHER->value, $this->input('target_languages', []))),
                'string',
            ],
            'summary' => ['required'],
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'key_words' => ['required', 'array'],
        ];
    }

    /**
     * Whether the submitted type uses the reduced form (fields in Type::REDUCED_FORM_FIELDS are not collected).
     */
    protected function isReducedForm(): bool
    {
        $type = $this->input('type');

        if (! is_string($type)) {
            return false;
        }

        return Type::tryFrom($type)?->hasReducedForm() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('url');

        if (! is_string($raw)) {
            return;
        }

        $normalized = UrlNormalizer::normalize($raw);

        if ($normalized !== null && $normalized !== $raw) {
            $this->merge(['url' => $normalized]);
        }
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'title',
            'type' => 'type',
            'closing_date' => 'closing date',
            'coverage_activity' => 'coverage activity',
            'implementation_location' => 'implementation location',
            'target_audience' => 'target audience',
            'summary' => 'summary',
            'url' => 'URL',
        ];
    }
}
