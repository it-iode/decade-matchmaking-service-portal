<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueDiagnose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump queue state (jobs per queue, failed jobs, queue config) to output and logs for debugging';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $report = [];

        try {
            $report['config'] = [
                'queue.default' => Config::get('queue.default'),
                'mail-templates.queue.connection' => Config::get('mail-templates.queue.connection'),
                'mail-templates.queue.queue_name' => Config::get('mail-templates.queue.queue_name'),
                'cache.default' => Config::get('cache.default'),
            ];

            $report['pending_jobs_by_queue'] = DB::table('jobs')
                ->selectRaw('queue, COUNT(*) as total, SUM(reserved_at IS NOT NULL) as reserved, FROM_UNIXTIME(MIN(created_at)) as oldest_created_at, FROM_UNIXTIME(MAX(created_at)) as newest_created_at')
                ->groupBy('queue')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $report['failed_jobs_by_queue_last_7d'] = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subDays(7))
                ->selectRaw('queue, COUNT(*) as total, MAX(failed_at) as latest_failure')
                ->groupBy('queue')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $lastFailed = DB::table('failed_jobs')->orderByDesc('id')->first();

            if ($lastFailed !== null) {
                $report['last_failed_job'] = [
                    'queue' => $lastFailed->queue,
                    'connection' => $lastFailed->connection,
                    'failed_at' => $lastFailed->failed_at,
                    'exception_first_line' => strtok($lastFailed->exception, "\n"),
                ];
            }
        } catch (Throwable $e) {
            $report['error'] = $e->getMessage();
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->line('QueueDiagnose: '.$json);
        Log::info('QueueDiagnose report', $report);

        return isset($report['error']) ? Command::FAILURE : Command::SUCCESS;
    }
}
