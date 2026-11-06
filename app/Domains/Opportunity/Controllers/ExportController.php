<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for exporting opportunities to CSV
 */
class ExportController extends Controller
{
    /**
     * Export opportunities to CSV file
     */
    public function __invoke(OpportunityExportService $exportService): StreamedResponse|RedirectResponse
    {
        try {
            Gate::authorize('viewAny', Opportunity::class);

            return $exportService->exportOpportunitiesCsv();
        } catch (\Exception $exception) {
            return redirect()
                ->route('admin.opportunity.list')
                ->with('error', 'Failed to export opportunities: '.$exception->getMessage());
        }
    }
}
