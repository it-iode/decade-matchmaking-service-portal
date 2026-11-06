<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Requests;

use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;

/**
 * Submission of an ODC travel support opportunity from the home page.
 *
 * The type is forced server-side so the reduced validation rules of the parent
 * request always apply, whatever the client sends.
 */
final class OdcTravelSupportPostRequest extends OpportunityPostRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createOdcTravelSupport', Opportunity::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['type' => Type::ODC_TRAVEL_SUPPORT->value]);
    }
}
