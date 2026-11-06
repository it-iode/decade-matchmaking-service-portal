<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue worker health and alert if issues detected';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pendingJobs = DB::table('jobs')
            ->where('available_at', '<=', Carbon::now()->timestamp)
            ->where('reserved_at', null)
            ->count();

        $stuckJobs = DB::table('jobs')
            ->where('reserved_at', '<', Carbon::now()->subMinutes(30)->timestamp)
            ->count();

        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', Carbon::now()->subDay())
            ->count();

        // Critical issues - jobs stuck in queue
        if ($stuckJobs > 10) {
            $this->error("CRITICAL: {$stuckJobs} jobs stuck in queue for over 30 minutes!");
            // In production, you might want to send alerts here
            return Command::FAILURE;
        }

        // Warning conditions
        if ($pendingJobs > 100) {
            $this->warn("WARNING: {$pendingJobs} jobs pending in queue");
        }

        if ($failedJobs > 20) {
            $this->warn("WARNING: {$failedJobs} failed jobs in the last 24 hours");
        }

        // All good
        $this->info("Queue health check passed - Pending: {$pendingJobs}, Stuck: {$stuckJobs}, Failed (24h): {$failedJobs}");
        
        return Command::SUCCESS;
    }
}
