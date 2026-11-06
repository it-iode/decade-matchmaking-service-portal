<?php

namespace App\Domains\Settings\Requests;

use App\Domains\Settings\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class SettingsPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authorized users to update settings
        // You can implement more specific authorization logic here
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * Only validate fields that are actually present in the request for optimization.
     */
    public function rules(): array
    {
        $rules = [];

        // Define validation rules for all possible settings
        $allValidationRules = $this->getAllValidationRules();

        // Only apply validation rules for fields that are present in the request
        // This supports optimized submissions with only changed fields
        foreach ($allValidationRules as $field => $rule) {
            if ($this->isFieldPresent($field)) {
                $rules[$field] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Get all possible validation rules for settings.
     */
    private function getAllValidationRules(): array
    {
        return [
            // Text-based settings
            Setting::SITE_NAME => ['nullable', 'string', 'max:255'],
            Setting::SITE_DESCRIPTION => ['nullable', 'string', 'max:1000'],
            Setting::HOMEPAGE_YOUTUBE_VIDEO => ['nullable', 'string', 'max:500'],
            Setting::SHOW_ODC_TRAVEL_SUPPORT_SECTION => ['nullable', 'in:0,1'],

            // Metric settings (integers)
            Setting::SUCCESSFUL_MATCHES_METRIC => ['nullable', 'integer', 'min:0'],
            Setting::COMMITTED_FUNDING_METRIC => ['nullable', 'integer', 'min:0'],
            Setting::FULLY_CLOSED_MATCHES_METRIC => ['nullable', 'integer', 'min:0'],
            Setting::REQUEST_IN_IMPLEMENTATION_METRIC => ['nullable', 'integer', 'min:0'],
            Setting::OPEN_PARTNER_OPPORTUNITIES_METRIC => ['nullable', 'integer', 'min:0'],

            // File upload settings
            Setting::PORTAL_GUIDE => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            Setting::USER_GUIDE => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            Setting::PARTNER_GUIDE => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            Setting::ORGANIZATIONS_CSV => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
            Setting::IOC_PLATFORMS_CSV => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
            Setting::MANDRILL_API_KEY => ['nullable', 'string', 'max:255'],

            // OAuth credentials
            Setting::LINKEDIN_CLIENT_ID => ['nullable', 'string', 'max:255'],
            Setting::LINKEDIN_CLIENT_SECRET => ['nullable', 'string', 'max:255'],
            Setting::GOOGLE_CLIENT_ID => ['nullable', 'string', 'max:255'],
            Setting::GOOGLE_CLIENT_SECRET => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Check if a field is present in the request.
     * Handles both regular fields and file uploads.
     */
    private function isFieldPresent(string $field): bool
    {
        return Setting::isFileUpload($field)
            ? $this->hasFile($field)
            : $this->has($field);
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'logo' => 'logo',
            'portal_guide' => 'portal guide',
            'user_guide' => 'user guide',
            'partner_guide' => 'partner guide',
            'site_name' => 'site name',
            'site_description' => 'site description',
            'homepage_youtube_video' => 'YouTube video URL',
            'show_odc_travel_support_section' => 'ODC travel support section visibility',
            'successful_matches_metric' => 'successful matches',
            'committed_funding_metric' => 'committed funding',
            'fully_closed_matches_metric' => 'fully closed matches',
            'request_in_implementation_metric' => 'requests in implementation',
            'open_partner_opportunities_metric' => 'open partner opportunities',
            'ioc_platforms_csv' => 'IOC platforms CSV file',
            'linkedin_client_id' => 'LinkedIn client ID',
            'linkedin_client_secret' => 'LinkedIn client secret',
            'google_client_id' => 'Google client ID',
            'google_client_secret' => 'Google client secret',
        ];
    }
}
