<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Services;

use App\Domains\Opportunity\Models\Opportunity;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting opportunity data to CSV format
 *
 * Handles generation of CSV exports with opportunity data including user info,
 * thematic areas, and location details. Uses streaming to handle large datasets
 * efficiently without memory issues.
 */
class OpportunityExportService
{
    private const NOT_APPLICABLE = 'N/A';

    public function __construct(
        private readonly OpportunityRepository $repository
    ) {}

    /**
     * Export opportunities to CSV file
     *
     * Generates a CSV file containing opportunity data with columns:
     * - user_name: Owner's first name
     * - user_last_name: Owner's last name
     * - user_email: Owner's email
     * - opportunity_id: Opportunity ID
     * - closing_date: Closing date (Y-m-d format)
     * - status: Status label
     * - title: Opportunity title
     * - summary: Opportunity summary text
     * - type: Type label
     * - thematic_areas: Comma-separated thematic area labels
     * - thematic_areas_other: Thematic Areas Other
     * - created_at: Creation timestamp (Y-m-d H:i:s format)
     * - coverage_activity: Coverage activity label
     * - implementation_location: Location label or "Global"
     * - target_audience: Comma-separated target audience labels
     * - target_audience_other: Target Audience Other
     * - target_languages: Comma-separated language labels
     * - target_languages_other: Other languages text
     * - url: Opportunity URL
     * - keywords: Comma-separated keywords
     *
     * @return StreamedResponse CSV file download response
     */
    public function exportOpportunitiesCsv(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                Log::error('Failed to open output stream for opportunity CSV export');

                return;
            }

            // Write CSV header
            fputcsv($handle, $this->getCsvColumnHeaders());

            // Stream opportunity data row-by-row
            foreach ($this->repository->getOpportunitiesForExport() as $opportunity) {
                fputcsv($handle, $this->formatOpportunityRow($opportunity));
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
            'User name',
            'Email',
            'Id',
            'Submitted date',
            'Closing date',
            'Status',
            'Institution or programme offering this opportunity',
            'Title',
            'Summary',
            'Type',
            'Thematic Areas',
            'Thematic Areas Other',
            'Coverage activity',
            'Implementation location',
            'Target audience',
            'Target audience other',
            'Target languages',
            'Target languages other',
            'Url',
            'Keywords',
        ];
    }

    /**
     * Format a single opportunity row for CSV export
     *
     * @return array<int, mixed>
     */
    private function formatOpportunityRow(Opportunity $opportunity): array
    {
        $notCollected = $opportunity->type?->hasReducedForm() ? self::NOT_APPLICABLE : '';

        return [
            ($opportunity->user?->first_name ?? '').' '.($opportunity->user?->last_name ?? ''),
            $opportunity->user?->email ?? '',
            $opportunity->id,
            $opportunity->created_at?->format('Y-m-d H:i:s') ?? '',
            $opportunity->closing_date?->format('Y-m-d') ?? '',
            $opportunity->status?->label() ?? '',
            $this->formatCoOrganizers($opportunity),
            $opportunity->title ?? '',
            $opportunity->summary ?? '',
            $opportunity->type?->label() ?? '',
            $this->formatThematicAreas($opportunity) ?: $notCollected,
            $opportunity->thematic_areas_other ?? '',
            $opportunity->coverage_activity?->label() ?? $notCollected,
            $this->formatImplementationLocation($opportunity) ?: $notCollected,
            $this->formatTargetAudience($opportunity),
            $opportunity->target_audience_other ?? '',
            $this->formatTargetLanguages($opportunity) ?: $notCollected,
            $opportunity->target_languages_other ?? '',
            $opportunity->url ?? '',
            $this->formatKeywords($opportunity),
        ];
    }

    /**
     * Format thematic areas as comma-separated labels
     */
    private function formatThematicAreas(Opportunity $opportunity): string
    {
        $areas = $opportunity->thematic_areas;

        if ($areas === null || $areas->isEmpty()) {
            return '';
        }

        return $areas->map(fn ($area) => $area->label())->implode(', ');
    }

    /**
     * Format implementation location for CSV
     *
     * Handles DynamicLocationCast which can return:
     * - "Global" string
     * - Country, Region, or Ocean enum with label() method
     * - Array of locations
     */
    private function formatImplementationLocation(Opportunity $opportunity): string
    {
        $location = $opportunity->implementation_location;

        if ($location === null) {
            return '';
        }

        // Handle "Global" string
        if (is_string($location)) {
            return $location;
        }

        // Handle array of locations
        if (is_array($location)) {
            return implode(', ', array_map(function ($loc) {
                if (is_string($loc)) {
                    return $loc;
                }
                if (is_object($loc) && method_exists($loc, 'label')) {
                    return $loc->label();
                }

                return (string) $loc;
            }, $location));
        }

        // Handle single enum with label() method
        if (is_object($location) && method_exists($location, 'label')) {
            return $location->label();
        }

        return (string) $location;
    }

    /**
     * Format target audience as comma-separated labels
     */
    private function formatTargetAudience(Opportunity $opportunity): string
    {
        $audience = $opportunity->target_audience;

        if ($audience === null) {
            return '';
        }

        $items = $audience->getArrayCopy();

        if (empty($items)) {
            return '';
        }

        return implode(', ', array_map(fn ($item) => $item->label(), $items));
    }

    /**
     * Format target languages as comma-separated labels
     */
    private function formatTargetLanguages(Opportunity $opportunity): string
    {
        $languages = $opportunity->target_languages;

        if ($languages === null) {
            return '';
        }

        $items = $languages->getArrayCopy();

        if (empty($items)) {
            return '';
        }

        return implode(', ', array_map(fn ($item) => $item->label(), $items));
    }

    /**
     * Format keywords as comma-separated string
     */
    private function formatKeywords(Opportunity $opportunity): string
    {
        $keywords = $opportunity->key_words;

        if ($keywords === null || ! is_array($keywords)) {
            return '';
        }

        return implode(', ', $keywords);
    }

    /**
     * Format co organizers as comma-separated string
     */
    private function formatCoOrganizers(Opportunity $opportunity): string
    {
        $coOrganizers = $opportunity->co_organizers;

        if ($coOrganizers === null || ! is_array($coOrganizers)) {
            return '';
        }

        return implode(', ', $coOrganizers);
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
            'Content-Disposition' => 'attachment; filename="opportunities_'.date('Y-m-d_His').'.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
