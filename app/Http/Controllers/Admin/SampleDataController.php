<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SampleDataRunRequest;
use App\Shared\Support\SampleDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Admin developer tool (local / staging only): reset the database to a fake
 * dataset. Routes are only registered when SampleDataSeeder::isAllowedEnvironment().
 * Generic page, hence app/Http rather than a domain.
 */
class SampleDataController extends Controller
{
    public const SUMMARY_SESSION_KEY = 'sample_data_summary';

    public function __construct(
        private readonly SampleDataService $sampleDataService,
    ) {}

    public function index(): Response
    {
        abort_unless($this->sampleDataService->isAvailable(), 404);

        return Inertia::render('admin/SampleData', [
            'title' => 'Sample data',
            'environment' => app()->environment(),
            'plan' => $this->sampleDataService->preview(),
            'summary' => session(self::SUMMARY_SESSION_KEY),
        ]);
    }

    public function run(SampleDataRunRequest $request): RedirectResponse
    {
        abort_unless($this->sampleDataService->isAvailable(), 404);

        // Purge + generation is synchronous; give it headroom on slow staging boxes.
        set_time_limit(300);

        try {
            $summary = $this->sampleDataService->run($request->user());
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.sample-data.index')
                ->with('error', 'Sample data generation failed: '.$e->getMessage());
        }

        return redirect()
            ->route('admin.sample-data.index')
            ->with('success', sprintf('Sample dataset generated in %ss.', $summary['duration_seconds']))
            ->with(self::SUMMARY_SESSION_KEY, $summary);
    }
}
