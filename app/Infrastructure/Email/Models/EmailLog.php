<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email Log Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $recipient_email
 * @property string|null $recipient_name
 * @property string $event_name
 * @property string $template_name
 * @property string|null $mandrill_id
 * @property string $status
 * @property string|null $mandrill_code
 * @property string|null $error_message
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $bounced_at
 * @property int $open_count
 * @property int $click_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static Builder|EmailLog byStatus(string $status)
 * @method static Builder|EmailLog byEvent(string $eventName)
 * @method static Builder|EmailLog byUser(int $userId)
 * @method static Builder|EmailLog recent(int $limit = 50)
 * @method static Builder|EmailLog delivered()
 * @method static Builder|EmailLog failed()
 * @method static Builder|EmailLog bounced()
 * @method static Builder|EmailLog opened()
 * @method static Builder|EmailLog clicked()
 * @method static Builder|EmailLog inDateRange(\DateTime $start, \DateTime $end)
 */
class EmailLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'recipient_email',
        'recipient_name',
        'event_name',
        'template_name',
        'mandrill_id',
        'status',
        'mandrill_code',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'open_count',
        'click_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    /**
     * Email status constants.
     */
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_OPENED = 'opened';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SPAM = 'spam';

    /**
     * Get the user that owns the email log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by event name.
     */
    public function scopeByEvent(Builder $query, string $eventName): Builder
    {
        return $query->where('event_name', $eventName);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent logs.
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope a query to get delivered emails.
     */
    public function scopeDelivered(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_DELIVERED,
            self::STATUS_OPENED,
            self::STATUS_CLICKED,
        ]);
    }

    /**
     * Scope a query to get failed emails.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_FAILED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Scope a query to get bounced emails.
     */
    public function scopeBounced(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_BOUNCED,
            self::STATUS_SPAM,
        ]);
    }

    /**
     * Scope a query to get opened emails.
     */
    public function scopeOpened(Builder $query): Builder
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope a query to get clicked emails.
     */
    public function scopeClicked(Builder $query): Builder
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInDateRange(Builder $query, \DateTime $start, \DateTime $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Check if the email was successfully sent.
     */
    public function wasSuccessful(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_OPENED,
            self::STATUS_CLICKED,
        ], true);
    }

    /**
     * Check if the email failed.
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_REJECTED,
            self::STATUS_BOUNCED,
            self::STATUS_SPAM,
        ], true);
    }

    /**
     * Check if the email was opened.
     */
    public function wasOpened(): bool
    {
        return $this->opened_at !== null;
    }

    /**
     * Check if the email was clicked.
     */
    public function wasClicked(): bool
    {
        return $this->clicked_at !== null;
    }

    /**
     * Update the status and set the appropriate timestamp.
     */
    public function updateStatus(string $status, ?string $errorMessage = null): void
    {
        $this->status = $status;

        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }

        switch ($status) {
            case self::STATUS_SENT:
                $this->sent_at = now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
            case self::STATUS_OPENED:
                if (! $this->opened_at) {
                    $this->opened_at = now();
                }
                $this->open_count = ($this->open_count ?? 0) + 1;
                break;
            case self::STATUS_CLICKED:
                if (! $this->clicked_at) {
                    $this->clicked_at = now();
                }
                $this->click_count = ($this->click_count ?? 0) + 1;
                break;
            case self::STATUS_BOUNCED:
            case self::STATUS_SPAM:
                $this->bounced_at = now();
                break;
        }

        $this->save();
    }

    /**
     * Get metadata value by key.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value by key.
     *
     * @param  mixed  $value
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
    }

    /**
     * Get delivery statistics for the log.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return [
            'status' => $this->status,
            'was_successful' => $this->wasSuccessful(),
            'was_opened' => $this->wasOpened(),
            'was_clicked' => $this->wasClicked(),
            'open_count' => $this->open_count ?? 0,
            'click_count' => $this->click_count ?? 0,
            'time_to_open' => $this->opened_at && $this->sent_at
                ? $this->opened_at->diffInSeconds($this->sent_at)
                : null,
            'time_to_click' => $this->clicked_at && $this->sent_at
                ? $this->clicked_at->diffInSeconds($this->sent_at)
                : null,
        ];
    }
}
