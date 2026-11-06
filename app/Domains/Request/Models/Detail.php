<?php

namespace App\Domains\Request\Models;

use App\Domains\Request\Enums\SubTheme;
use App\Domains\Request\Enums\SupportType;
use App\Domains\Request\Enums\TargetAudience;
use App\Shared\Enums\Country;
use App\Shared\Enums\Language;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detail extends Model
{
    protected $table = 'request_details';

    protected $fillable = [
        'request_id',
        'capacity_development_title',
        'is_related_decade_action',
        'unique_related_decade_action_id',
        'first_name',
        'last_name',
        'email',
        'has_significant_changes',
        'changes_description',
        'change_effect',
        'request_link_type',
        'project_stage',
        'project_url',
        'related_activity',
        'delivery_format',
        'delivery_countries',
        'subthemes',
        'decade_challenges',
        'support_types',
        'target_audience',
        'target_languages',
        'subthemes_other',
        'support_types_other',
        'target_audience_other',
        'target_languages_other',
        'gap_description',
        'has_partner',
        'partner_name',
        'partner_confirmed',
        'needs_financial_support',
        'budget_breakdown',
        'support_months',
        'completion_date',
        'risks',
        'personnel_expertise',
        'direct_beneficiaries',
        'direct_beneficiaries_number',
        'expected_outcomes',
        'success_metrics',
        'long_term_impact',
        'additional_data',
    ];

    protected function casts(): array
    {
        return [
            'subthemes' => AsEnumArrayObject::of(SubTheme::class),
            'decade_challenges' => 'array',
            'support_types' => AsEnumArrayObject::of(SupportType::class),
            'target_audience' => AsEnumArrayObject::of(TargetAudience::class),
            'target_languages' => AsEnumArrayObject::of(Language::class),
            'delivery_countries' => AsEnumArrayObject::of(Country::class),
            'additional_data' => 'array',
            'support_months' => 'integer',
            'direct_beneficiaries_number' => 'integer',
            'completion_date' => 'datetime:Y-m-d',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}
