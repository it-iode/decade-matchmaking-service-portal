<?php

declare(strict_types=1);

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Models\Request;
use App\Domains\Request\Services\RequestExportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for exporting requests to CSV
 */
class ExportCsvController extends Controller
{
    /**
     * Export requests to CSV file
     */
    public function __invoke(RequestExportService $exportService): StreamedResponse|RedirectResponse
    {
        try {
            Gate::authorize('viewAny', Request::class);

            return $exportService->exportRequestsCsv();
        } catch (\Exception $exception) {
            return redirect()
                ->route('admin.request.list')
                ->with('error', 'Failed to export requests: '.$exception->getMessage());
        }
    }
}
