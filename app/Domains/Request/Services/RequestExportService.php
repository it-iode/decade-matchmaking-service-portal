<?php

declare(strict_types=1);

namespace App\Domains\Request\Services;

use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Models\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting request data to CSV format
 *
 * Handles generation of CSV exports with request data including user info,
 * contact details, thematic areas, and project information. Uses streaming
 * to handle large datasets efficiently without memory issues.
 */
class RequestExportService
{
    public function __construct(
        private readonly RequestRepository $repository
    ) {}

    /**
     * Export requests to CSV file
     *
     * Generates a CSV file containing request data with comprehensive columns
     * covering all request and detail fields.
     *
     * @return StreamedResponse CSV file download response
     */
    public function exportRequestsCsv(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                Log::error('Failed to open output stream for request CSV export');

                return;
            }

            // Write CSV header
            fputcsv($handle, $this->getCsvColumnHeaders());

            // Stream request data row-by-row
            foreach ($this->repository->getRequestsForExport() as $request) {
                fputcsv($handle, $this->formatRequestRow($request));
            }

            fclose($handle);
        }, 200, $this->getHttpHeaders());
    }

    /**
     * Get CSV column headers
     *
     * @return array<int, string>
     */
    private function getCsvColumnHeaders(): array
    {
        return [
            // Request base info
            'ID',
            'Status',
            'Submitted At',
            // Submitter info (from User)
            'Submitter Name',
            'Submitter Email',
            // Contact info (from Detail)
            'Contact First Name',
            'Contact Last Name',
            'Contact Email',
            // Project identification
            'Capacity Development Title',
            'Is this request related to an Ocean Decade Action',
            'Unique Action ID',
            // Project details
            'Request Link Type',
            'Project Stage',
            'Project URL',
            'Related Activity',
            // Delivery
            'Delivery Format',
            'Delivery Countries',
            // Thematic areas
            'Primary Decade Challenge',
            'Secondary Decade Challenge',
            'Tertiary Decade Challenge',
            'Subthemes',
            'Subthemes Other',
            'Support Types',
            'Support Types Other',
            // Target audience
            'Target Audience',
            'Target Audience Other',
            'Target Languages',
            'Target Languages Other',
            // Changes info
            'Has your Action undergone any significant changes since its endorsement',
            'Please explain any changes in your Action since submission',
            'Change Effect',
            // Partner info
            'Gap Description',
            'Has Partner',
            'Partner Name',
            'Partner Confirmed',
            // Financial
            'Needs Financial Support',
            'Budget Breakdown',
            'Support Months',
            'By when do you anticipate completing this activity',
            // Impact
            'Please identify and describe any risks you anticipate in implementing this request and contingency measures',
            'Personnel Expertise',
            'Direct Beneficiaries',
            'Direct Beneficiaries Number',
            'Expected Outcomes',
            'Success Metrics',
            'Long Term Impact',
        ];
    }

    /**
     * Format a single request row for CSV export
     *
     * @return array<int, mixed>
     */
    private function formatRequestRow(Request $request): array
    {
        $detail = $request->detail;

        return [
            // Request base info
            $request->id,
            $request->status?->status_label ?? '',
            $request->created_at?->format('Y-m-d H:i:s') ?? '',
            // Submitter info
            ($request->user?->first_name ?? '').' '.($request->user?->last_name ?? ''),
            $request->user?->email ?? '',
            // Contact info
            $detail?->first_name ?? '',
            $detail?->last_name ?? '',
            $detail?->email ?? '',
            // Project identification
            $detail?->capacity_development_title ?? '',
            $detail?->is_related_decade_action ? 'Yes' : 'No',
            $detail?->unique_related_decade_action_id ?? '',
            // Project details
            $detail?->request_link_type ?? '',
            $detail?->project_stage ?? '',
            $detail?->project_url ?? '',
            $detail?->related_activity ?? '',
            // Delivery
            $detail?->delivery_format ?? '',
            $this->formatEnumArray($detail?->delivery_countries),
            // Thematic areas
            $this->formatChallengeLabel($detail?->decade_challenges, 'primary'),
            $this->formatChallengeLabel($detail?->decade_challenges, 'secondary'),
            $this->formatChallengeLabel($detail?->decade_challenges, 'tertiary'),
            $this->formatEnumArray($detail?->subthemes),
            $detail?->subthemes_other ?? '',
            $this->formatEnumArray($detail?->support_types),
            $detail?->support_types_other ?? '',
            // Target audience
            $this->formatEnumArray($detail?->target_audience),
            $detail?->target_audience_other ?? '',
            $this->formatEnumArray($detail?->target_languages),
            $detail?->target_languages_other ?? '',
            // Changes info
            $detail?->has_significant_changes ? 'Yes' : 'No',
            $detail?->changes_description ?? '',
            $detail?->change_effect ?? '',
            // Partner info
            $detail?->gap_description ?? '',
            $detail?->has_partner ? 'Yes' : 'No',
            $detail?->partner_name ?? '',
            $detail?->partner_confirmed ? 'Yes' : 'No',
            // Financial
            $detail?->needs_financial_support ? 'Yes' : 'No',
            $detail?->budget_breakdown ?? '',
            $detail?->support_months ?? '',
            $detail?->completion_date?->format('Y-m-d') ?? '',
            // Impact
            $detail?->risks ?? '',
            $detail?->personnel_expertise ?? '',
            $detail?->direct_beneficiaries ?? '',
            $detail?->direct_beneficiaries_number ?? '',
            $detail?->expected_outcomes ?? '',
            $detail?->success_metrics ?? '',
            $detail?->long_term_impact ?? '',
        ];
    }

    /**
     * Resolve a ranked Decade Challenge value to its label for export
     *
     * @param  mixed  $challenges  Raw `decade_challenges` array from the detail model
     * @param  string  $rank  One of 'primary', 'secondary', 'tertiary'
     */
    private function formatChallengeLabel(mixed $challenges, string $rank): string
    {
        if (! is_array($challenges)) {
            return '';
        }

        $value = $challenges[$rank] ?? null;

        if (! is_string($value) || $value === '') {
            return '';
        }

        return DecadeChallenge::getLabelByValue($value) ?? $value;
    }

    /**
     * Format enum array objects as comma-separated labels
     *
     * Handles AsEnumArrayObject casts that return ArrayObject instances
     * containing enum values with label() methods.
     *
     * @param  mixed  $enumArray  The enum array object or null
     * @return string Comma-separated labels or empty string
     */
    private function formatEnumArray(mixed $enumArray): string
    {
        if ($enumArray === null) {
            return '';
        }

        // Handle ArrayObject from AsEnumArrayObject cast
        if (is_object($enumArray) && method_exists($enumArray, 'getArrayCopy')) {
            $items = $enumArray->getArrayCopy();

            if (empty($items)) {
                return '';
            }

            return implode(', ', array_map(fn ($item) => $item->label(), $items));
        }

        // Handle regular array
        if (is_array($enumArray)) {
            if (empty($enumArray)) {
                return '';
            }

            return implode(', ', array_map(function ($item) {
                if (is_object($item) && method_exists($item, 'label')) {
                    return $item->label();
                }

                return (string) $item;
            }, $enumArray));
        }

        return '';
    }

    /**
     * Get HTTP headers for CSV download
     *
     * @return array<string, string>
     */
    private function getHttpHeaders(): array
    {
        return [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="requests_'.date('Y-m-d_His').'.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
