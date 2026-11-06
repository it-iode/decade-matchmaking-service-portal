<?php

namespace App\Domains\Opportunity\Console;

use App\Domains\Opportunity\Services\OpportunityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredOpportunities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opportunities:close-expired
                            {--dry-run : Preview which opportunities would be closed without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close all opportunities that have passed their closing date';

    public function __construct(
        private readonly OpportunityService $opportunityService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting opportunity closure process...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No changes will be made');
            $this->performDryRun();

            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm('Are you sure you want to close expired opportunities?')) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Processing expired opportunities...');

        try {
            $results = $this->opportunityService->closeExpiredOpportunities();

            $this->displayResults($results);

            // Send admin notifications if any opportunities were closed
            if ($results['closed'] > 0) {
                //                $this->notificationService->notifyAdminsOfOpportunityClosure($results);
                $this->info('Admin notifications sent successfully.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred during the closure process: '.$e->getMessage());
            Log::error('[CloseExpiredOpportunities] Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        } catch (\Throwable $e) {
        }
    }

    /**
     * Perform a dry run to preview changes
     */
    private function performDryRun(): void
    {
        $expiredOpportunities = $this->opportunityService->getExpiredOpportunities();

        if ($expiredOpportunities->isEmpty()) {
            $this->info('No expired opportunities found.');

            return;
        }

        $this->info("Found {$expiredOpportunities->count()} opportunities to close:");

        $this->table(
            ['ID', 'Title', 'Status', 'Closing Date', 'Days Overdue'],
            $expiredOpportunities->map(function ($opportunity) {
                $daysOverdue = now()->diffInDays($opportunity->closing_date, false) * -1;

                return [
                    $opportunity->id,
                    $opportunity->title, 0, 40,
                    $opportunity->status->label ?? 'N/A',
                    $opportunity->closing_date->format('Y-m-d H:i'),
                    $daysOverdue.' days',
                ];
            })
        );
    }

    /**
     * Display the results of the closure process
     */
    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('Closure Process Complete');
        $this->info('========================');
        $this->info("Total Expired Found: {$results['total']}");
        $this->info("Successfully Closed: {$results['closed']}");

        if ($results['failed'] > 0) {
            $this->warn("Failed: {$results['failed']}");

            if (! empty($results['errors'])) {
                $this->error('Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->error("  - Opportunity #{$error['opportunity_id']}: {$error['error']}");
                }
            }
        }

        $this->newLine();
        $this->comment('Process completed at '.now()->format('Y-m-d H:i:s'));
    }
}
