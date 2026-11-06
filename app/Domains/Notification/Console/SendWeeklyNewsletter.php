<?php

declare(strict_types=1);

namespace App\Domains\Notification\Console;

use App\Domains\Notification\Services\NewsletterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendWeeklyNewsletter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newsletter:send-weekly
                            {--dry-run : Run without actually sending emails}
                            {--force : Force send even if recently sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly opportunities newsletter to users with active notification preferences';

    /**
     * Execute the console command.
     */
    public function handle(NewsletterService $newsletterService): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🚀 Starting weekly opportunities newsletter send process...');

        try {
            // Check if newsletter was recently sent (within last 6 days) unless forced
            if (! $force) {
                $lastSend = Cache::get('newsletter:last_send_date');
                if ($lastSend && now()->diffInDays($lastSend) < 6) {
                    $this->warn('⚠️  Newsletter was sent recently. Use --force to override.');
                    $this->info("Last sent: {$lastSend->diffForHumans()}");

                    return self::SUCCESS;
                }
            }

            if ($isDryRun) {
                $this->warn('🧪 DRY RUN MODE - No emails will be sent');

                // Get stats without sending
                $stats = $newsletterService->getNewsletterStats();
                $this->displayStats($stats);

                return self::SUCCESS;
            }

            // Send the weekly opportunities newsletter
            $this->info('📧 Dispatching weekly opportunities newsletters...');
            $stats = $newsletterService->sendWeeklyNewsletter();

            // Store last send timestamp and stats
            Cache::put('newsletter:last_send_date', now(), now()->addDays(14));
            Cache::put('newsletter:last_send_stats', $stats, now()->addDays(14));

            // Display results
            $this->newLine();
            $this->info('✅ Weekly opportunities newsletter send completed!');
            $this->displayResults($stats);

            // Log completion
            Log::info('[SendWeeklyNewsletter] Command completed successfully', $stats);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send weekly opportunities newsletter');
            $this->error("Error: {$e->getMessage()}");

            Log::error('[SendWeeklyNewsletter] Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Display newsletter statistics (dry run)
     */
    private function displayStats(array $stats): void
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Subscribed Users', $stats['subscribed_users'] ?? 0],
                ['Last Send', $stats['last_send'] ? $stats['last_send']->diffForHumans() : 'Never'],
            ]
        );
    }

    /**
     * Display newsletter send results
     */
    private function displayResults(array $stats): void
    {
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Users Processed', $stats['processed'] ?? 0],
                ['Emails Sent', $stats['sent'] ?? 0],
                ['Failed', $stats['failed'] ?? 0],
            ]
        );

        // Display errors if any
        if (! empty($stats['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Newsletter Errors:');
            foreach ($stats['errors'] as $error) {
                $this->line("  • User #{$error['user_id']} ({$error['email']}): {$error['error']}");
            }
        }

        $totalSent = $stats['sent'] ?? 0;
        if ($totalSent > 0) {
            $this->newLine();
            $this->info("📨 {$totalSent} newsletters have been queued for delivery");
        }
    }
}
