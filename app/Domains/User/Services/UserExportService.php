<?php

declare(strict_types=1);

namespace App\Domains\User\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting user data to CSV format
 *
 * Handles generation of CSV exports with user data including roles and OAuth provider.
 * Uses streaming to handle large datasets efficiently without memory issues.
 */
class UserExportService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Export users to CSV file
     *
     * Generates a CSV file containing user data with columns:
     * - id: User ID
     * - user name: Full name
     * - email: Email address
     * - provider: OAuth provider (google, linkedin, or empty)
     * - roles: Comma-separated role names
     * - country: Country name or code
     * - created_at: Account creation timestamp (Y-m-d H:i:s format)
     * - is_blocked: Block status (Yes/No)
     *
     * @return StreamedResponse CSV file download response
     */
    public function exportUsersCsv(): StreamedResponse
    {
        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                Log::error('Failed to open output stream for CSV export');

                return;
            }

            // Write CSV header
            fputcsv($handle, [
                'id',
                'user name',
                'email',
                'provider',
                'roles',
                'country',
                'created_at',
                'is_blocked',
            ]);

            // Stream user data row-by-row
            foreach ($this->repository->getUsersForExport() as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name ?? '',
                    $user->email ?? '',
                    $user->provider ?? '',
                    $user->roles->pluck('name')->implode(','),
                    $user->country ?? '',
                    $user->created_at?->format('Y-m-d H:i:s') ?? '',
                    $user->is_blocked ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 200, $this->getCsvHeaders());
    }

    /**
     * Get HTTP headers for CSV download
     *
     * @return array<string, string>
     */
    private function getCsvHeaders(): array
    {
        return [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users_'.date('Y-m-d_His').'.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }
}
