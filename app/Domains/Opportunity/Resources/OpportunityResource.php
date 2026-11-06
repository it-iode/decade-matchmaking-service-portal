<?php

namespace App\Domains\Opportunity\Resources;

use App\Domains\Opportunity\Models\Opportunity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Opportunity
 */
class OpportunityResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Opportunity $opportunity */
        $opportunity = $this->resource;
        $baseData = [
            'id' => $opportunity->id,
            'co_organizers' => $opportunity->co_organizers,
            'title' => $opportunity->title,
            'type' => [
                'value' => $opportunity->type->value,
                'label' => $opportunity->type->label(),
            ],
            'status' => [
                'value' => $opportunity->status->value,
                'label' => $opportunity->status->label(),
            ],
            'closing_date' => Carbon::parse($opportunity->closing_date)->toDateString(),
            'coverage_activity' => $opportunity->coverage_activity ? [
                'value' => $opportunity->coverage_activity->value,
                'label' => $opportunity->coverage_activity->label(),
            ] : null,
            'implementation_location' => $this->transformEnumArray($opportunity->implementation_location),
            'thematic_areas' => $this->transformEnumArray($opportunity->thematic_areas),
            'thematic_areas_other' => $opportunity->thematic_areas_other,
            'target_audience' => $this->transformEnumArray($opportunity->target_audience),
            'target_audience_other' => $opportunity->target_audience_other,
            'target_languages' => $this->transformEnumArray($opportunity->target_languages),
            'target_languages_other' => $opportunity->target_languages_other,
            'summary' => $opportunity->summary,
            'url' => $opportunity->url,
            'apply_url' => route('opportunity.go', ['identifier' => $opportunity->public_id]),
            'created_at' => $opportunity->created_at,
            'updated_at' => $opportunity->updated_at,
            'key_words' => $opportunity->key_words,
        ];
        if ($request->user()) {
            $baseData['user'] = $this->whenLoaded('user');
            $baseData['permissions'] = [
                'can_view' => $request->user()?->can('view', [Opportunity::class, $opportunity]) ?? false,
                'can_edit' => $request->user()?->can('update', [Opportunity::class, $opportunity]) ?? false,
                'can_apply' => $request->user()?->can('apply', [Opportunity::class, $opportunity]) ?? false,
                'can_extend' => $request->user()?->can('extend', [Opportunity::class, $opportunity]) ?? false,
                'can_delete' => $request->user()?->can('delete', [Opportunity::class, $opportunity]) ?? false,
                'can_approve' => $request->user()?->can('approve', [Opportunity::class, $opportunity]) ?? false,
                'can_reject' => $request->user()?->can('reject', [Opportunity::class, $opportunity]) ?? false,
                'can_close' => $request->user()?->can('close', [Opportunity::class, $opportunity]) ?? false,
            ];
        }

        return $baseData;
    }

    /**
     * Transform an enum array to value/label format.
     *
     * @param  mixed  $enumArray
     * @return array<int, array<string, string>>
     */
    private function transformEnumArray($enumArray): array
    {
        if (! $enumArray) {
            return [];
        }

        $result = [];
        foreach ($enumArray as $enum) {
            $result[] = [
                'value' => $enum->value,
                'label' => $enum->label(),
            ];
        }

        return $result;
    }
}
