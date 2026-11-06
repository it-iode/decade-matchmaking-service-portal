<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Console;

use App\Infrastructure\Email\Services\HealthCheckService;
use Illuminate\Console\Command;

class EmailHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:health-check
                          {--json : Output results in JSON format}
                          {--fail-on-error : Exit with error code if unhealthy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of the email system';

    /**
     * Execute the console command.
     */
    public function handle(HealthCheckService $healthCheckService): int
    {
        $this->info('Performing email system health check...');
        $this->newLine();

        $results = $healthCheckService->check();

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return $results['overall_status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        }

        // Display results in table format
        $this->displayResults($results);

        $failOnError = $this->option('fail-on-error');

        if ($results['overall_status'] === 'healthy') {
            $this->newLine();
            $this->info('✓ Email system is healthy');

            return self::SUCCESS;
        }

        if ($results['overall_status'] === 'critical') {
            $this->newLine();
            $this->error('✗ Email system is in critical state');

            return $failOnError ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();
        $this->warn('⚠ Email system is degraded');

        return self::SUCCESS;
    }

    /**
     * Display health check results
     *
     * @param  array<string, mixed>  $results
     */
    protected function displayResults(array $results): void
    {
        // Overall status
        $statusEmoji = match ($results['overall_status']) {
            'healthy' => '✓',
            'degraded' => '⚠',
            'critical' => '✗',
            default => '?',
        };

        $this->line("Overall Status: {$statusEmoji} {$results['overall_status']}");
        $this->newLine();

        // Mandrill status
        $this->line('<fg=cyan>Mandrill API:</>');
        $this->line('  Status: '.$results['mandrill']['status']);
        if ($results['mandrill']['status'] === 'healthy') {
            $this->line('  Username: '.($results['mandrill']['username'] ?? 'N/A'));
            $this->line('  Reputation: '.($results['mandrill']['reputation'] ?? 'N/A'));
        } else {
            $this->line('  Error: '.($results['mandrill']['error'] ?? 'Unknown'));
        }
        $this->newLine();

        // Queue status
        $this->line('<fg=cyan>Queue Status:</>');
        $this->line('  Status: '.$results['queue']['status']);
        $this->line('  Connection: '.($results['queue']['connection'] ?? 'N/A'));
        $this->line('  Queue Name: '.($results['queue']['queue_name'] ?? 'N/A'));
        $this->line('  Pending Jobs: '.($results['queue']['pending_jobs'] ?? 0));
        $this->line('  Recent Failed Jobs: '.($results['queue']['recent_failed_jobs'] ?? 0));
        $this->newLine();

        // Database status
        $this->line('<fg=cyan>Database Status:</>');
        $this->line('  Status: '.$results['database']['status']);
        $this->line('  Recent Logs: '.($results['database']['recent_logs'] ?? 0));
        $this->newLine();

        // Delivery rates
        $this->line('<fg=cyan>Delivery Rates (Last 24 Hours):</>');
        $rates = $results['delivery_rates'];
        $this->line('  Total Sent: '.$rates['total']);
        $this->line('  Delivered: '.$rates['delivered'].' ('.$rates['delivery_rate'].'%)');
        $this->line('  Failed: '.$rates['failed']);
        $this->line('  Bounced: '.$rates['bounced']);
        $this->line('  Failure Rate: '.$rates['failure_rate'].'%');
        $this->newLine();

        // Recent failures
        $failures = $results['recent_failures'];
        $this->line('<fg=cyan>Recent Failures (Last Hour):</>');
        $this->line('  Total: '.$failures['total']);
        if (! empty($failures['by_event'])) {
            $this->line('  By Event:');
            foreach ($failures['by_event'] as $event => $count) {
                $this->line("    - {$event}: {$count}");
            }
        }
    }
}
