<?php

namespace App\Domains\Request\Resources;

use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Models\Detail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Detail
 */
class DetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        $currentUser = $request->user();
        $requestOwner = $this->resource->request?->user;
        $isOwner = $currentUser && $requestOwner && $currentUser->id === $requestOwner->id;
        $isAdmin = $currentUser && $currentUser->is_admin;
        $isSubscriber = $this->resource->request?->subscriptions->contains('user_id', $currentUser->id);

        // Determine if user has full access (owner or admin)
        $hasFullAccess = $isOwner || $isAdmin || $isSubscriber;

        // Public attributes - always visible
        $publicAttributes = [
            'capacity_development_title' => $this->capacity_development_title,
            'related_activity' => $this->related_activity,
            'delivery_format' => $this->delivery_format,
            'decade_challenges' => $this->transformRankedChallenges($this->decade_challenges),
            'subthemes' => $this->transformEnumArray($this->subthemes),
            'subthemes_other' => $this->subthemes_other,
            'support_types' => $this->transformEnumArray($this->support_types),
            'support_types_other' => $this->support_types_other,
            'target_audience' => $this->transformEnumArray($this->target_audience),
            'target_audience_other' => $this->target_audience_other,
            'target_languages' => $this->transformEnumArray($this->target_languages),
            'target_languages_other' => $this->target_languages_other,
            'delivery_countries' => $this->transformEnumArray($this->delivery_countries),
            'gap_description' => $this->gap_description,
            'support_months' => $this->support_months,
            'completion_date' => $this->completion_date?->format('Y-m-d'),
            'expected_outcomes' => $this->expected_outcomes,
            'success_metrics' => $this->success_metrics,
            'long_term_impact' => $this->long_term_impact,
            'project_stage' => $this->project_stage,
            'project_url' => $this->project_url,
        ];

        // Private attributes - only for owner or admin
        if ($hasFullAccess) {
            $privateAttributes = [
                'is_related_decade_action' => $this->is_related_decade_action,
                'unique_related_decade_action_id' => $this->unique_related_decade_action_id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'has_significant_changes' => $this->has_significant_changes,
                'changes_description' => $this->changes_description,
                'change_effect' => $this->change_effect,
                'request_link_type' => $this->request_link_type,
                'has_partner' => $this->has_partner,
                'partner_name' => $this->partner_name,
                'partner_confirmed' => $this->partner_confirmed,
                'needs_financial_support' => $this->needs_financial_support,
                'budget_breakdown' => $this->budget_breakdown,
                'risks' => $this->risks,
                'personnel_expertise' => $this->personnel_expertise,
                'direct_beneficiaries' => $this->direct_beneficiaries,
                'direct_beneficiaries_number' => $this->direct_beneficiaries_number,
                'additional_data' => $this->additional_data,
            ];

            return array_merge($publicAttributes, $privateAttributes);
        }

        return $publicAttributes;
    }

    /**
     * Transform the ranked Decade Challenges into {rank => {value, label}|null}.
     *
     * @param  mixed  $challenges  Raw `decade_challenges` array from the model cast
     * @return array{
     *     primary: array{value: string, label: string}|null,
     *     secondary: array{value: string, label: string}|null,
     *     tertiary: array{value: string, label: string}|null
     * }
     */
    private function transformRankedChallenges(mixed $challenges): array
    {
        $transformed = [
            'primary' => null,
            'secondary' => null,
            'tertiary' => null,
        ];

        if (! is_array($challenges)) {
            return $transformed;
        }

        foreach (array_keys($transformed) as $rank) {
            $value = $challenges[$rank] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $transformed[$rank] = [
                'value' => $value,
                'label' => DecadeChallenge::getLabelByValue($value) ?? $value,
            ];
        }

        return $transformed;
    }

    /**
     * Transform enum array to include both value and label
     */
    private function transformEnumArray($enumArray): array
    {
        if (! $enumArray) {
            return [];
        }

        // Handle string values (shouldn't happen with proper casting, but defensive programming)
        if (is_string($enumArray)) {
            // If it's a JSON string, decode it
            $decoded = json_decode($enumArray, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $enumArray = $decoded;
            } else {
                // Single string value
                return [[
                    'value' => $enumArray,
                    'label' => $enumArray,
                ]];
            }
        }

        // Ensure we have an array or iterable
        if (! is_array($enumArray) && ! is_iterable($enumArray)) {
            return [];
        }

        $result = [];
        foreach ($enumArray as $enum) {
            if ($enum && method_exists($enum, 'label')) {
                $result[] = [
                    'value' => $enum->value,
                    'label' => $enum->label(),
                ];
            } elseif ($enum) {
                // Fallback for enums without label method or string values
                $result[] = [
                    'value' => is_string($enum) ? $enum : $enum->value,
                    'label' => is_string($enum) ? $enum : $enum->value,
                ];
            }
        }

        return $result;
    }
}
