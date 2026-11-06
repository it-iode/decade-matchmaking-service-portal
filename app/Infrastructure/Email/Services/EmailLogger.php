<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Services;

use App\Domains\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles logging of email operations
 */
class EmailLogger
{
    private bool $enabled;

    private string $logChannel;

    public function __construct()
    {
        $this->enabled = Config::get('mail-templates.logging.enabled', true);
        $this->logChannel = Config::get('mail-templates.logging.channel', 'stack');
    }

    /**
     * Log an email being queued
     */
    public function logQueued(
        string $eventName,
        string $templateName,
        string $recipientEmail,
        ?User $user = null,
        array $metadata = []
    ): int {
        if (! $this->enabled) {
            return 0;
        }

        // Only use user_id if the user exists in the database (has a valid positive id)
        // Temporary User objects created for non-user recipients have id=0
        $userId = ($user !== null && $user->exists && $user->id > 0) ? $user->id : null;

        $logId = $this->insertDatabaseLog([
            'user_id' => $userId,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $user?->name ?? $metadata['recipient_name'] ?? null,
            'event_name' => $eventName,
            'template_name' => $templateName,
            'status' => 'queued',
            'metadata' => json_encode($metadata),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->logToFile('info', 'Email queued', [
            'log_id' => $logId,
            'event' => $eventName,
            'template' => $templateName,
            'recipient' => $recipientEmail,
        ]);

        return $logId;
    }

    /**
     * Log successful email send
     */
    public function logSent(
        int $logId,
        string $mandrillId,
        array $response = []
    ): void {
        if (! $this->enabled) {
            return;
        }

        $this->updateDatabaseLog($logId, [
            'mandrill_id' => $mandrillId,
            'status' => 'sent',
            'sent_at' => Carbon::now(),
            'metadata' => DB::raw("JSON_MERGE_PATCH(metadata, '".json_encode(['response' => $response])."')"),
        ]);

        $this->logToFile('info', 'Email sent successfully', [
            'log_id' => $logId,
            'mandrill_id' => $mandrillId,
        ]);
    }

    /**
     * Log email delivery (webhook callback)
     */
    public function logDelivered(string $mandrillId): void
    {
        if (! $this->enabled) {
            return;
        }

        DB::table('email_logs')
            ->where('mandrill_id', $mandrillId)
            ->update([
                'status' => 'delivered',
                'updated_at' => Carbon::now(),
            ]);

        $this->logToFile('info', 'Email delivered', [
            'mandrill_id' => $mandrillId,
        ]);
    }

    /**
     * Log email bounce
     */
    public function logBounced(string $mandrillId, string $reason): void
    {
        if (! $this->enabled) {
            return;
        }

        DB::table('email_logs')
            ->where('mandrill_id', $mandrillId)
            ->update([
                'status' => 'bounced',
                'error_message' => $reason,
                'updated_at' => Carbon::now(),
            ]);

        $this->logToFile('warning', 'Email bounced', [
            'mandrill_id' => $mandrillId,
            'reason' => $reason,
        ]);
    }

    /**
     * Log email rejection
     */
    public function logRejected(
        int $logId,
        string $reason,
        array $errorDetails = []
    ): void {
        if (! $this->enabled) {
            return;
        }

        $this->updateDatabaseLog($logId, [
            'status' => 'rejected',
            'error_message' => $reason,
            'metadata' => DB::raw("JSON_MERGE_PATCH(metadata, '".json_encode(['error' => $errorDetails])."')"),
        ]);

        $this->logToFile('error', 'Email rejected', [
            'log_id' => $logId,
            'reason' => $reason,
            'details' => $errorDetails,
        ]);
    }

    /**
     * Log email failure
     */
    public function logFailed(
        string $eventName,
        string $recipientEmail,
        string $error,
        array $context = []
    ): void {
        if (! $this->enabled) {
            return;
        }

        $this->insertDatabaseLog([
            'user_id' => $context['user_id'] ?? null,
            'recipient_email' => $recipientEmail,
            'event_name' => $eventName,
            'template_name' => $context['template_name'] ?? '',
            'status' => 'failed',
            'error_message' => $error,
            'metadata' => json_encode($context),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->logToFile('error', 'Email failed', [
            'event' => $eventName,
            'recipient' => $recipientEmail,
            'error' => $error,
            'context' => $context,
        ]);
    }

    /**
     * Get email logs for a user
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUserLogs(User $user, int $limit = 50)
    {
        return DB::table('email_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get email statistics
     *
     * @return array<string, mixed>
     */
    public function getStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $query = DB::table('email_logs');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $stats = $query
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($stats);

        return [
            'total' => $total,
            'queued' => $stats['queued'] ?? 0,
            'sent' => $stats['sent'] ?? 0,
            'delivered' => $stats['delivered'] ?? 0,
            'bounced' => $stats['bounced'] ?? 0,
            'rejected' => $stats['rejected'] ?? 0,
            'failed' => $stats['failed'] ?? 0,
            'delivery_rate' => $total > 0 ? (($stats['delivered'] ?? 0) / $total) * 100 : 0,
        ];
    }

    /**
     * Clean up old email logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        $deleted = DB::table('email_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->logToFile('info', 'Cleaned up old email logs', [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);

        return $deleted;
    }

    /**
     * Insert a log entry into the database
     */
    private function insertDatabaseLog(array $data): int
    {
        // Check if the table exists first
        if (! $this->tableExists()) {
            // Return 0 if table doesn't exist (migration not run yet)
            return 0;
        }

        return DB::table('email_logs')->insertGetId($data);
    }

    /**
     * Update a log entry in the database
     */
    private function updateDatabaseLog(int $logId, array $data): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $data['updated_at'] = Carbon::now();
        DB::table('email_logs')->where('id', $logId)->update($data);
    }

    /**
     * Check if the email_logs table exists
     */
    private function tableExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable('email_logs');
    }

    /**
     * Log to file system
     */
    private function logToFile(string $level, string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->$level("EmailLogger: {$message}", $context);
    }
}
