<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Infrastructure\Email\Exceptions\MandrillApiException;
use App\Infrastructure\Email\Models\EmailLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Health check service for email system monitoring
 */
class HealthCheckService
{
    public function __construct(
        private readonly MandrillClient $mandrillClient
    ) {}

    /**
     * Perform comprehensive health check
     *
     * @return array<string, mixed>
     */
    public function check(): array
    {
        return [
            'overall_status' => $this->getOverallStatus(),
            'mandrill' => $this->checkMandrillConnectivity(),
            'queue' => $this->checkQueueStatus(),
            'database' => $this->checkDatabaseStatus(),
            'delivery_rates' => $this->getDeliveryRates(),
            'recent_failures' => $this->getRecentFailures(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get overall system health status
     */
    protected function getOverallStatus(): string
    {
        $mandrill = $this->checkMandrillConnectivity();
        $queue = $this->checkQueueStatus();
        $database = $this->checkDatabaseStatus();

        if ($mandrill['status'] === 'healthy' && $queue['status'] === 'healthy' && $database['status'] === 'healthy') {
            return 'healthy';
        }

        if ($mandrill['status'] === 'unhealthy' || $database['status'] === 'unhealthy') {
            return 'critical';
        }

        return 'degraded';
    }

    /**
     * Check Mandrill API connectivity
     *
     * @return array<string, mixed>
     */
    protected function checkMandrillConnectivity(): array
    {
        try {
            // Try to fetch user info as a health check
            $info = $this->mandrillClient->getUserInfo();

            return [
                'status' => 'healthy',
                'username' => $info['username'] ?? 'unknown',
                'reputation' => $info['reputation'] ?? null,
                'hourly_quota' => $info['hourly_quota'] ?? null,
            ];
        } catch (MandrillApiException $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'code' => $e->getMandrillCode(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue status
     *
     * @return array<string, mixed>
     */
    protected function checkQueueStatus(): array
    {
        try {
            $queueName = Config::get('mail-templates.queue.queue_name', 'emails');
            $connection = Config::get('mail-templates.queue.connection', 'database');

            // Count pending jobs
            $pendingJobs = DB::table('jobs')
                ->where('queue', $queueName)
                ->count();

            // Count failed jobs in last hour
            $recentFailedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count();

            $status = 'healthy';
            if ($pendingJobs > 100) {
                $status = 'degraded';
            }
            if ($recentFailedJobs > 10) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'connection' => $connection,
                'queue_name' => $queueName,
                'pending_jobs' => $pendingJobs,
                'recent_failed_jobs' => $recentFailedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database status
     *
     * @return array<string, mixed>
     */
    protected function checkDatabaseStatus(): array
    {
        try {
            // Try to query email_logs table
            $recentCount = EmailLog::where('created_at', '>=', now()->subHour())->count();

            return [
                'status' => 'healthy',
                'recent_logs' => $recentCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get email delivery rates
     *
     * @return array<string, mixed>
     */
    protected function getDeliveryRates(): array
    {
        $last24Hours = now()->subDay();

        $total = EmailLog::where('created_at', '>=', $last24Hours)->count();

        if ($total === 0) {
            return [
                'period' => '24 hours',
                'total' => 0,
                'delivered' => 0,
                'failed' => 0,
                'bounced' => 0,
                'delivery_rate' => 0,
            ];
        }

        $delivered = EmailLog::where('created_at', '>=', $last24Hours)
            ->delivered()
            ->count();

        $failed = EmailLog::where('created_at', '>=', $last24Hours)
            ->failed()
            ->count();

        $bounced = EmailLog::where('created_at', '>=', $last24Hours)
            ->bounced()
            ->count();

        return [
            'period' => '24 hours',
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'bounced' => $bounced,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round((($failed + $bounced) / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent failures
     *
     * @return array<string, mixed>
     */
    protected function getRecentFailures(): array
    {
        $lastHour = now()->subHour();

        $failures = EmailLog::where('created_at', '>=', $lastHour)
            ->failed()
            ->get()
            ->groupBy('event_name')
            ->map(fn ($group) => $group->count())
            ->toArray();

        $totalFailures = array_sum($failures);

        return [
            'period' => '1 hour',
            'total' => $totalFailures,
            'by_event' => $failures,
            'status' => $totalFailures > 10 ? 'warning' : 'normal',
        ];
    }

    /**
     * Check if the system is healthy
     */
    public function isHealthy(): bool
    {
        $status = $this->check();

        return $status['overall_status'] === 'healthy';
    }
}
